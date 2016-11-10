'use strict';

var TextlineView = require('./Textline');
var CheckboxView = require('./Checkbox');
var RadioButtonView = require('./RadioButton');

module.exports = function (element) {

  this.el = element;
  this.init();

};

module.exports.prototype = {

  init: function () {

    console.log('Init Form');

    this.setVariables();
    this.addEventListeners();

    this.initFormElements();

  },

  setVariables: function () {

    this.validated = true;
    this.submit = this.el.querySelector('.js-form-submit');
    this.elements = this.el.querySelectorAll('.js-form-component');

  },

  addEventListeners: function () {

    this.submit.addEventListener('click', function () {

      this.submitForm();

    }.bind(this));

  },

  initFormElements: function () {

    for (var i = 0; i < this.elements.length; i++) {

      if (this.elements[i].classList.contains('js-form-component-textline')) {
        new TextlineView(this.elements[i]);
      } else if (this.elements[i].classList.contains('js-form-component-checkbox')) {
        new CheckboxView(this.elements[i]);
      } else if (this.elements[i].classList.contains('js-form-component-radio-button')) {
        new RadioButtonView(this.elements[i]);
      }

    }

  },

  submitForm: function () {

    this.validateForm();

  },

  validateForm: function () {

    this.validated = true;
    this.getElements();

    if (this.validated) {
      this.el.submit();
    }

  },

  getElements: function () {

    for (var i = 0; i < this.elements.length; i++) {

      this.validateElement(this.elements[i]);

    }

  },

  validateElement: function (element) {

    var type = element.querySelector('input').type;

    if (type == 'checkbox') {
      this.handleCheckboxValidation(element);
    } else if (type == 'text') {
      this.handleTextlineValidation(element);
    } else if (type == 'radio') {
      this.handleRadioButtonValidation(element);
    }

  },

  handleCheckboxValidation: function (element) {

    var result = FV.validateCheckbox(element);

    if (!result.passed) {
      this.validated = false;
      EH.addErrorState(element, result.message);
    }

  },

  handleTextlineValidation: function (element) {

    var result = FV.validateTextline(element);

    if (!result.passed) {
      this.validated = false;
      EH.addErrorState(element, result.message);
    }

  },

  handleRadioButtonValidation: function (element) {

    var result = FV.validateRadioButton(element);

    if (!result.passed) {
      this.validated = false;
      EH.addErrorState(element, result.message);
    }

  }

};
