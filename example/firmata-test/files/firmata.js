(function($) {
  
  var pin = {

    board : null,
    element : null,
    number : 0,
    mode : 'output',
    digital : 'yes',
    analog : 0.0,
    value : 0,
    blockUpdates : false,
    isChanged : false,
    
    template : 
      '<div class="span4">' +
        '<form id="pin{X}" class="pin-control well clearfix">' +
          '<h3 class="form-heading">Pin {X}</h3>' +
          '<div class="control-group pin-modes">' +
            '<div class="controls btn switch switch-five">' +
              '<input id="pin{X}_mode_output" name="pin2_mode" value="output" type="radio">' +
              '<label for="pin{X}_mode_output">output</label>' +
              '<input id="pin{X}_mode_input" name="pin2_mode" value="input" type="radio">' +
              '<label for="pin{X}_mode_input">input</label>' +
              '<input id="pin{X}_mode_pwm" name="pin2_mode" value="pwm" type="radio">' +
              '<label for="pin{X}_mode_pwm">pwm</label>' +
              '<input id="pin{X}_mode_analog" name="pin2_mode" value="analog" type="radio">' +
              '<label for="pin{X}_mode_analog">analog</label>' +
              '<input id="pin{X}_mode_servo" name="pin2_mode" value="servo" type="radio">' +
              '<label for="pin{X}_mode_servo">servo</label>' +
              '<span class="slide-button btn btn-success"></span>' +
            '</div>' +
          '</div>' +
          '<div class="control-group pin-mode pin-mode-output">' +
            '<div class="controls btn switch switch-two">' +
              '<input id="pin{X}_digital_yes" name="pin{X}_digital" value="yes" type="radio">' +
              '<label for="pin{X}_digital_yes">on</label>' +
              '<input id="pin{X}_digital_no" name="pin{X}_digital" value="no" type="radio">' +
              '<label for="pin{X}_digital_no">off</label>' +
              '<span class="slide-button btn btn-success"></span>' +
            '</div>' +
          '</div>' +
          '<div class="control-group pin-mode pin-mode-input" style="display: none;">' +
            '<div class="controls btn switch switch-two disabled">' +
              '<input id="pin{X}_digital_yes" name="pin{X}_digital" value="yes" type="radio" disabled>' +
              '<label for="pin{X}_digital_yes" class="disabled">on</label>' +
              '<input id="pin{X}_digital_no" name="pin{X}_digital" value="no" type="radio" disabled>' +
              '<label for="pin{X}_digital_no" class="disabled">off</label>' +
              '<span class="slide-button btn btn-success"></span>' +
            '</div>' +
          '</div>' +
          '<div class="pin-mode pin-mode-pwm" style="display: none;">' +
            '<input type="number" id="pin{X}_pwm" min="0" max="255" step="1">' +
          '</div>' +
          '<div class="pin-mode pin-mode-analog" style="display: none;">' +
            '<div class="progress progress-success progress-striped" id="pin{X}_analog">' +
              '<div class="bar" style="width: 20%;"></div>' +
            '</div>' +
          '</div>' +
          '<div class="pin-mode pin-mode-servo" style="display: none;">' +
            '<input type="number" id="pin{X}_servo" min="0" max="255" step="1">' +
          '</div>' +
        '</form>' +
      '</div>',
      
      
    setUp : function(board, pinData) {
      this.board = board;
      this.number = pinData.attr('number');
      board.container.append(this.template.replace(/\{X\}/g, this.number));
      this.element = board.container.find('#pin' + this.number);
      this.element.find('.pin-modes input').each(
        function () {
          var input = $(this);
          var mode = input.val();
          if (!(pinData.is('[supports~=' + mode + ']'))) {
            input.prop('disabled', true);
            input.parent().find('label[for=' + input.attr('id') +']').addClass('disabled');
          }
        }  
      );
      this.element.find('.pin-mode-input label').click(
        function(event) {
          event.stopPropagation();
          return false;
        }
      );
      this.element.find('input[type="radio"]').click(
          $.proxy(this.send, this)
        );
      this.element.find('input[type="number"],input[type="range"]').change(
          $.proxy(this.send, this)
        );
      this.update(pinData);
      return this;
    },
  
    update : function(pinData) {
      if (this.blockUpdates) {
        return;
      }
      if (typeof pinData != 'undefined') {
        this.mode = pinData.attr('mode');
        this.digital = pinData.attr('digital');
        if (typeof this.digital == 'undefined') {
          this.digital = 'no';
        }
        this.analog = pinData.attr('analog');
        if (typeof this.analog == 'undefined') {
          this.analog = 0.0;
        }
        this.value = pinData.attr('analog');
        if (typeof this.value == 'undefined') {
          this.value = 0;
        }
      }
      var selected = '.pin-mode-' + this.mode;
      this.element.find('.pin-modes input[value="' + this.mode + '"]').prop('checked', true);
      this.element.find('.pin-mode').not(selected).hide();
      this.element.find(selected).show();
      switch (this.mode) {
      case 'output' :
        this.element.find('.pin-mode-output input[value="' + this.digital + '"]').prop('checked', true);
        break;
      case 'input' :
        if (!this.element.find('.pin-mode-input input[value="' + this.digital + '"]').is(':checked')) {
          this.element.find('.pin-mode-input input[value="' + this.digital + '"]').prop('checked', true);
        }
        break;
      case 'pwm' :
        this.element.find('.pin-mode-pwm input:not(:focus)').val(this.value);
        break;
      case 'analog' :
        var $percent = Math.round(parseFloat(this.analog) * 100).toString() + '%'; 
        this.element.find('.pin-mode-analog .progress .bar')
          .css('width', $percent)
          .text(this.value);
        break;
      case 'servo' :
        this.element.find('.pin-mode-servo input:not(:focus)').val(this.value);
        break;
      }
    },
    
    send : function () {
      if (this.blockUpdates) {
        this.isChanged = true;
      }
      this.blockUpdates = true;
      this.mode = this.element.find('.pin-modes input:checked').attr('value');
      var url = this.board.options.url + '/' + this.number + '?mode=' + this.mode;
      switch (this.mode) {
      case 'output' :
        this.digital = this.element.find('.pin-mode-output input:checked').attr('value');
        url += '&digital=' + this.digital;
        break;
      case 'input' :
      case 'analog' :
        break;
      case 'pwm' :
        this.value = this.element.find('.pin-mode-pwm input').prop('value');
        url += '&value=' + this.value;
        break;
      case 'servo' :
        this.value = this.element.find('.pin-mode-servo input').prop('value');
        url += '&value=' + this.value;
        break;
      }
      this.update();
      var that = this;
      $.ajax(
         {
           url : url,
           cache : false
         }
       )
       .always(
         function () {
           that.blockUpdates = false;
         }
       )
       .done(
         function (response) {
           that.blockUpdates = false;
           $(response).find('board pin').each(
             function() {
               var key = 'pin' + $(this).attr('number');
               that.board.pins[key].update($(this));
             }  
           );
           if (that.isChanged) {
             that.isChanged = false;
             that.send();
           }
         }
       );
    }      
  };
  
  var board = {
    
    container : null,
    pins : {},
    
    options : {
      url : '../pins'
    },
    
    setUp : function(container, options) {
      this.container = container;
      this.options = $.extend(this.options, options);
      var that = this;
      $.ajax(
         {
           url : this.options.url,
           cache : false
         }
       )
       .done(
         function (response) {
           $(response).find('board pin').each(
             function() {
               var key = 'pin' + $(this).attr('number');
               that.pins[key] = $.extend(true, {}, pin).setUp(that, $(this));
             }  
           );
           that.schedule();
         }
       );
      return this;
    },
    
    schedule : function() {
      var that = this;
      window.setTimeout(
        function() {
          $.ajax(
             {
               url : that.options.url,
               cache : false
             }
           )
           .done(
             function (response) {
               $(response).find('board pin').each(
                 function() {
                   var key = 'pin' + $(this).attr('number');
                   that.pins[key].update($(this));
                 }  
               );
             }
           )
           .always(
             function() {
               that.schedule();
             }
           );
        },
        1000
      );
    }
    
  };
  
  $.fn.firmata = function(options) {
    var container = $(this).eq(0);
    if (container.length > 0) {
      board.setUp(container, options);
    };
  };
  
})(jQuery);