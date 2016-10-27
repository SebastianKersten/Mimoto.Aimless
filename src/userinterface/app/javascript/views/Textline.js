'use strict';

module.exports = function (element) {

  this.el = element;
  this.init();

};

module.exports.prototype = {

  init: function () {

    console.log('Init textline');
    this.setVariables();
    this.addEventListeners();

    this.initErrorhandling();

  },

  initErrorhandling: function () {

    this.errorHandling = new ErrorHandling(this.el, {
      "element": "p",
      "classes": ["form-component-element-error"],
      "errorClass": "form-component--has-error",
      "validatedClass": "form-component--is-validated",
      "iconSelectorClass": "js-error-icon",
      "iconErrorClass": "form-component-title-icon--warning",
      "iconValidatedClass": "form-component-title-icon--checkmark"
    });

    this.validation = new Validation({
      "minLength": this.minLength,
      "maxLength": this.maxLength
    });

  },

  setVariables: function () {

    this.input = this.el.querySelector('.js-form-input');

    this.minLength = this.input.getAttribute('data-min-length');
    this.maxLength = this.input.getAttribute('data-max-length');

  },

  addEventListeners: function () {

    this.input.addEventListener('keyup', function () {

      this.handleValidation(this.input.value);

    }.bind(this));

  },

  handleValidation: function (value) {

    if (value.length == 0) {

      this.errorHandling.clearState();

    } else {

      var validated = this.validation.validate(value);

      if (validated) {

        this.errorHandling.clearState();
        this.errorHandling.isValidated();

      } else {

        this.errorHandling.addError("Too long/short", this.el);

      }

    }

  }

};
