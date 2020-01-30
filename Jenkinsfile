def runTests( phpVersion ) {
    docker.image( "wordpressdevelop/php:${phpVersion}-fpm" ).inside {
        stage( "${phpVersion} Tests" ){
            try {
                sh "vendor/bin/phpunit -c phpunit.xml.dist --log-junit build/logs/junit-${phpVersion}.xml"
            } finally {
                junit "build/logs/junit-${phpVersion}.xml"
            }
        }
    }
}

node( 'docker-agent' ) {
    checkout scm
    def workspace = pwd()
    docker.image( 'yoastseo/docker-php-composer-node:latest' ).inside {
        stage( 'Install' ) {
            cache( maxCacheSize: 250, caches: [
                [ $class: 'ArbitraryFileCache', excludes: '', includes: '**/*', path: './vendor' ]
            ] ) {
                sh 'composer install --no-interaction'
            }

            sh 'mkdir -p build/logs'
        }
        parallel(
            other: {
                docker.image( 'wordpressdevelop/php:7.3-fpm' ).inside {
                    parallel(
                        phplint: {
                            stage( 'Linting' ) {
                                sh 'find -L . -path ./vendor -prune -o -path ./node_modules -prune -o -name "*.php" -print0 | xargs -0 -n 1 -P 4 php -l'
                            }
                        },
                        phpcs: {
                            stage( 'Codestyle' ) {
                                try {
                                    sh 'vendor/bin/phpcs --report=checkstyle --report-file=`pwd`/build/logs/checkstyle.xml'
                                } finally {
                                    def checkstyle = scanForIssues tool: checkStyle(pattern: 'build/logs/checkstyle.xml')
                                    publishIssues issues: [checkstyle]
                                }
                            }
                        },
                        phpmd: {
                            stage( 'Mess detection' ) {
                                try {
                                    sh 'vendor/bin/phpmd . xml cleancode,codesize,design,naming,unusedcode --reportfile build/logs/pmd.xml --exclude vendor/,build/ --ignore-violations-on-exit'
                                } finally {
                                    def pmd = scanForIssues tool: pmdParser(pattern: 'build/logs/pmd.xml')
                                    publishIssues issues: [pmd]
                                }
                            }
                        },
                        copypaste: {
                            stage('Copy paste detection') {
                                try {
                                    sh 'vendor/bin/phpcpd --log-pmd build/logs/pmd-cpd.xml --exclude vendor --exclude build . || exit 0'
                                } finally {
                                    def cpd = scanForIssues tool: cpd(pattern: 'build/logs/pmd-cpd.xml')
                                    publishIssues issues: [cpd]
                                }
                            }
                        },
                        securitycheck: {
                            stage( 'Security check' ) {
                                sh 'vendor/bin/security-checker security:check composer.lock'
                            }
                        }
                    )
                }
            },
            php73: {
                // Run tests with code coverage
                docker.image( "wordpressdevelop/php:7.3-fpm" ).inside {
                    stage( "7.3 Tests" ){
                        sh 'docker-php-ext-enable xdebug'
                        try {
                            sh "vendor/bin/phpunit -c phpunit.xml.dist --log-junit build/logs/junit-7.3.xml --coverage-html build/coverage --coverage-clover build/logs/clover.xml"
                        } finally {
                            junit "build/logs/junit-7.3.xml"
                            step ([
                                $class: 'CloverPublisher',
                                cloverReportDir: "build/coverage",
                                cloverReportFileName: "../logs/clover.xml",
                                healthyTarget: [ methodCoverage: 70, conditionalCoverage: 80, statementCoverage: 80 ],
                                unhealthyTarget: [ methodCoverage: 50, conditionalCoverage: 50, statementCoverage: 50 ],
                                failingTarget: [ methodCoverage: 0, conditionalCoverage: 0, statementCoverage: 0 ]
                            ] )
                        }
                    }
                }
            },
            php74: {
                runTests( '7.4' );
            },
            php56: {
                runTests( '5.6' );
            }
        )
    }
}