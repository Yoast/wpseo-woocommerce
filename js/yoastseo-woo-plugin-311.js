(function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
(function() {

	var AssessmentResult = require( 'yoastseo/js/values/AssessmentResult' );

	/**
	 * Adds eventlistener to load the Yoast WooCommerce plugin
	 */
	if ( typeof YoastSEO !== 'undefined' && typeof YoastSEO.app !== 'undefined' ) {
		new YoastWooCommercePlugin();
	}
	else {
		jQuery( window ).on(
			'YoastSEO:ready',
			function() {
				new YoastWooCommercePlugin();
			}
		);
	}

	/**
	 * Registers Plugin and Test for Yoast WooCommerce.
	 */
	function YoastWooCommercePlugin() {
		YoastSEO.app.registerPlugin( 'YoastWooCommerce', { 'status': 'ready' } );

		YoastSEO.app.registerAssessment( 'productTitle', { getResult: this.productDescription.bind( this ) }, 'YoastWooCommerce' );

		this.addCallback();

		YoastSEO.app.registerPlugin( 'YoastWooCommercePlugin', { status: 'ready' } );

		this.registerModifications();

		this.bindEvents();
	}

	/**
	 * Strip double spaces from text
	 *
	 * @param {String} text The text to strip spaces from.
	 * @returns {String} The text without double spaces
	 */
	var stripSpaces = function( text ) {

		// Replace multiple spaces with single space
		text = text.replace( /\s{2,}/g, ' ' );

		// Replace spaces followed by periods with only the period.
		text = text.replace( /\s\./g, '.' );

		// Remove first/last character if space
		text = text.replace( /^\s+|\s+$/g, '' );

		return text;
	};

	/**
	 * Strip HTML-tags from text
	 *
	 * @param {String} text The text to strip the HTML-tags from.
	 * @returns {String} The text without HTML-tags.
	 */
	var stripTags = function( text ) {
		text = text.replace( /(<([^>]+)>)/ig, ' ' );
		text = stripSpaces( text );
		return text;
	};

	/**
	 * Tests the length of the productdescription.
	 * @param {object} paper The paper to run this assessment on
	 * @param {object} researcher The researcher used for the assessment
	 * @param {object} i18n The i18n-object used for parsing translations
	 * @returns {object} an assessmentresult with the score and formatted text.
	 */
	YoastWooCommercePlugin.prototype.productDescription = function( paper, researcher, i18n ) {
		var productDescription = document.getElementById( 'excerpt' ).value;
		if (typeof tinyMCE !== 'undefined' && tinyMCE.get( 'excerpt') !== null) {
			productDescription = tinyMCE.get( 'excerpt').getContent();
		}

		productDescription = stripTags( productDescription );
		var result = this.scoreProductDescription( productDescription.split( ' ' ).length );
		var assessmentResult = new AssessmentResult();
		assessmentResult.setScore( result.score );
		assessmentResult.setText( result.text );
		return assessmentResult;
	};

	/**
	 * Returns the score based on the lengt of the product description.
	 * @param {number} length The length of the product description.
	 * @returns {{score: number, text: *}} The result object with score and text.
	 */
	YoastWooCommercePlugin.prototype.scoreProductDescription = function( length ) {

		if ( length === 0 ) {
			return {
				score: 1,
				text: wpseoWooL10n.woo_desc_none
			};
		}

		if ( length > 0 && length < 20 ) {
			return {
				score: 5,
				text: wpseoWooL10n.woo_desc_short
			};
		}

		if ( length >= 20 && length <= 50 ) {
			return {
				score: 9,
				text: wpseoWooL10n.woo_desc_good
			};
		}
		if ( length > 50 ) {
			return {
				score: 5,
				text: wpseoWooL10n.woo_desc_long
			};
		}
	};

	/**
	 * Adds callback to the excerpt field to trigger the analyzeTimer when product description is updated.
	 * The tinyMCE triggers automatically since that inherets the binding from the content field tinyMCE.
	 */
	YoastWooCommercePlugin.prototype.addCallback = function() {
		var elem = document.getElementById( 'excerpt' );
		if( elem !== null ){
			elem.addEventListener( 'input', YoastSEO.app.analyzeTimer.bind( YoastSEO.app ) );
		}

	};

	/**
	 * binds events to the add_product_images anchor.
	 */
	YoastWooCommercePlugin.prototype.bindEvents = function() {
		jQuery( '.add_product_images' ).find( 'a' ).on( 'click', this.bindLinkEvent.bind( this ) );

	};

	/**
	 * counters for the setTimeouts, used to make sure we don't end up in an infinite loop.
	 * @type {number}
	 */
	var buttonEventCounter = 0;
	var deleteEventCounter = 0;

	/**
	 * after the modal dialog is opened, check for the button that adds images to the gallery to trigger
	 * the modification.
	 */
	YoastWooCommercePlugin.prototype.bindLinkEvent = function() {
		if (jQuery( '.media-modal-content' ).find( '.media-button' ).length === 0 ) {
			buttonEventCounter++;
			if ( buttonEventCounter < 10 ) {
				setTimeout( this.bindLinkEvent.bind( this ) );
			}
		} else {
			buttonEventCounter = 0;
			jQuery( '.media-modal-content' ).find( '.media-button' ).on( 'click', this.buttonCallback.bind( this )  );
		}
	};

	/**
	 * After the gallery is added, call the analyzeTimer of the app, to add the modifications.
	 * Also call the bindDeleteEvent, to bind the analyzerTimer when an image is deleted.
	 */
	YoastWooCommercePlugin.prototype.buttonCallback = function() {
		YoastSEO.app.analyzeTimer();
		this.bindDeleteEvent();
	};

	/**
	 * Checks if the delete buttons of the added images are available. When they are, bind the analyzeTimer function
	 * so when a image is removed, the modification is run.
	 */
	YoastWooCommercePlugin.prototype.bindDeleteEvent = function() {
		if ( jQuery( '#product_images_container' ).find( '.delete' ).length === 0 ){
			deleteEventCounter++;
			if ( deleteEventCounter < 10 ) {
				setTimeout( this.bindDeleteEvent.bind( this ) );
			}
		} else {
			deleteEventCounter = 0;
			jQuery( '#product_images_container' ).find( '.delete' ).on( 'click', YoastSEO.app.analyzeTimer.bind( YoastSEO.app ) );
		}
	};

	/**
	 * Registers the addImageToContent modification
	 */
	YoastWooCommercePlugin.prototype.registerModifications = function() {
		var callback = this.addImageToContent.bind( this );

		YoastSEO.app.registerModification( 'content', callback, 'YoastWooCommercePlugin', 10 );
	};

	/**
	 * Adds the images from the pagegallery to the content to be analyzed by the analyzer.
	 * @param data {String}
	 * @returns {String}
	 */
	YoastWooCommercePlugin.prototype.addImageToContent = function( data ) {
		var images = jQuery( '#product_images_container' ).find( 'img' );

		for (var i = 0; i < images.length; i++ ){
			data += images[ i ].outerHTML;
		}
		return data;
	};
}
());

},{"yoastseo/js/values/AssessmentResult":2}],2:[function(require,module,exports){
var isUndefined = require( "lodash/isUndefined" );
var isNumber = require( "lodash/isNumber" );

/**
 * A function that only returns an empty that can be used as an empty marker
 *
 * @returns {Array} A list of empty marks.
 */
var emptyMarker = function() {
	return [];
};

/**
 * Construct the AssessmentResult value object.
 *
 * @param {Object} values The values for this assessment result.
 *
 * @constructor
 */
var AssessmentResult = function( values ) {
	this._hasScore = false;
	this._identifier = "";
	this._hasMarks = false;
	this._marker = emptyMarker;
	this.score = 0;
	this.text = "";

	if ( isUndefined( values ) ) {
		values = {};
	}

	if ( !isUndefined( values.score ) ) {
		this.setScore( values.score );
	}

	if ( !isUndefined( values.text ) ) {
		this.setText( values.text );
	}
};

/**
 * Check if a score is available.
 * @returns {boolean} Whether or not a score is available.
 */
AssessmentResult.prototype.hasScore = function() {
	return this._hasScore;
};

/**
 * Get the available score
 * @returns {number} The score associated with the AssessmentResult.
 */
AssessmentResult.prototype.getScore = function() {
	return this.score;
};

/**
 * Set the score for the assessment.
 * @param {number} score The score to be used for the score property
 * @returns {void}
 */
AssessmentResult.prototype.setScore = function( score ) {
	if ( isNumber( score ) ) {
		this.score = score;
		this._hasScore = true;
	}
};

/**
 * Check if a text is available.
 * @returns {boolean} Whether or not a text is available.
 */
AssessmentResult.prototype.hasText = function() {
	return this.text !== "";
};

/**
 * Get the available text
 * @returns {string} The text associated with the AssessmentResult.
 */
AssessmentResult.prototype.getText = function() {
	return this.text;
};

/**
 * Set the text for the assessment.
 * @param {string} text The text to be used for the text property
 * @returns {void}
 */
AssessmentResult.prototype.setText = function( text ) {
	if ( isUndefined( text ) ) {
		text = "";
	}

	this.text = text;
};

/**
 * Sets the identifier
 *
 * @param {string} identifier An alphanumeric identifier for this result.
 */
AssessmentResult.prototype.setIdentifier = function( identifier ) {
	this._identifier = identifier;
};

/**
 * Gets the identifier
 *
 * @returns {string} An alphanumeric identifier for this result.
 */
AssessmentResult.prototype.getIdentifier = function() {
	return this._identifier;
};

/**
 * Sets the marker, a pure function that can return the marks for a given Paper
 *
 * @param {Function} marker The marker to set.
 */
AssessmentResult.prototype.setMarker = function( marker ) {
	this._marker = marker;
};

/**
 * Returns whether or not this result has a marker that can be used to mark for a given Paper
 *
 * @returns {boolean} Whether or this result has a marker.
 */
AssessmentResult.prototype.hasMarker = function() {
	return this._hasMarks && this._marker !== emptyMarker;
};

/**
 * Gets the marker, a pure function that an return the marks for a given Paper
 *
 * @returns {Function} The marker.
 */
AssessmentResult.prototype.getMarker = function() {
	return this._marker;
};

/**
 * Sets the value of _hasMarks to determine if there is something to mark.
 *
 * @param {boolean} hasMarks Is there something to mark.
 */
AssessmentResult.prototype.setHasMarks = function( hasMarks ) {
	this._hasMarks = hasMarks;
};

/**
 * Returns the value of _hasMarks to determine if there is something to mark.
 *
 * @returns {boolean} Is there something to mark.
 */
AssessmentResult.prototype.hasMarks = function() {
	return this._hasMarks;
};

module.exports = AssessmentResult;

},{"lodash/isNumber":3,"lodash/isUndefined":5}],3:[function(require,module,exports){
var isObjectLike = require('./isObjectLike');

/** `Object#toString` result references. */
var numberTag = '[object Number]';

/** Used for built-in method references. */
var objectProto = Object.prototype;

/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/6.0/#sec-object.prototype.tostring)
 * of values.
 */
var objectToString = objectProto.toString;

/**
 * Checks if `value` is classified as a `Number` primitive or object.
 *
 * **Note:** To exclude `Infinity`, `-Infinity`, and `NaN`, which are
 * classified as numbers, use the `_.isFinite` method.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is correctly classified,
 *  else `false`.
 * @example
 *
 * _.isNumber(3);
 * // => true
 *
 * _.isNumber(Number.MIN_VALUE);
 * // => true
 *
 * _.isNumber(Infinity);
 * // => true
 *
 * _.isNumber('3');
 * // => false
 */
function isNumber(value) {
  return typeof value == 'number' ||
    (isObjectLike(value) && objectToString.call(value) == numberTag);
}

module.exports = isNumber;

},{"./isObjectLike":4}],4:[function(require,module,exports){
/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return !!value && typeof value == 'object';
}

module.exports = isObjectLike;

},{}],5:[function(require,module,exports){
/**
 * Checks if `value` is `undefined`.
 *
 * @static
 * @since 0.1.0
 * @memberOf _
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is `undefined`, else `false`.
 * @example
 *
 * _.isUndefined(void 0);
 * // => true
 *
 * _.isUndefined(null);
 * // => false
 */
function isUndefined(value) {
  return value === undefined;
}

module.exports = isUndefined;

},{}]},{},[1]);
