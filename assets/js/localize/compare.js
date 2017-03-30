/**
 * Wraps edit view form-groups, showing the compare UI on hover
 */
define(function (require) {

  // Dependencies
  var $ = require('jquery')
    , _ = require('lodash')
    , Backbone = require('backbone')
  ;

  // Get a shared reference to the localizations
  var $localizations = $('.form-group.compare :radio');
  if (!$localizations.length) return;

  // Store the model data when localizations change
  var model, locale;
  $localizations.on('change', function() {
    var $checked = $localizations.filter(':checked');
    model = $checked.data('model');
    locale = $checked.siblings('.locale').text();
  });

  // Popover defaults
  var defaults = {
    container: 'body',
    html: true,
    placement: 'right',
    template: '<div class="popover localize-compare" role="tooltip"><div class="arrow"></div><div class="popover-title"></div><div class="popover-content"></div></div>',
    viewport: { selector: 'body', padding: 5 },
    trigger: 'manual'
  };

  // Setup view
  var View = {};
  View.initialize = function() {
    _.bindAll(this);

    // Cache
    try { this.$input = this.getInput(); }
    catch (e) { return; }
    this.type = this.getType();
    this.name = this.getName();

    // Register events
    this.$el.on('mouseenter', this.show);
    this.$el.on('mouseleave', this.hide);

  };

  // Get the input
  View.getInput = function() {

    // If an image input, find the
    if (this.$el.hasClass('image-upload')) {
      return this.$('.input-name');
    }

    // If simple constraints only match 1 field, use it
    $input = this.$('input,textarea');
    if ($input.length == 1) return $input;

    // Otherwise, throw an error
    throw new Error('Input could not be detected');
  };

  // Figure out what type of form element is being shown
  View.getType = function() {
    if (this.$input.attr('name') == 'slug') return 'slug';
    else if (this.$input.attr('name') == 'locale') return 'locale';
    else if (this.$input.hasClass('wysiwyg')) return 'wysiwyg';
    else if (this.$input.hasClass('date')) return 'date';
    else if (this.$input.hasClass('input-name')) return 'image';
    else if (this.$input.is(':radio')) return 'radio';
    else return 'text';
  };

  // Get the attribute name
  View.getName = function() {
    if (this.type == 'image') return this.$input.val() || 'default';
    else return this.$input.attr('name');
  }

  // Show the popover
  View.show = function() {
    if (!model) return;

    // Get the massaged content
    var content = this.getContent();
    if (!content) return;

    // Merge this title and value into with defaults and show
    this.$el.popover(_.defaults({
      title: locale + ' localization',
      content: content
    }, defaults)).popover('show');
  };

  // Massage the content of the popover
  View.getContent = function() {

    // Get the content value
    var content = this.type == 'image' ?
        model.images[this.name] :
        model[this.name];

    // Massage the content
    switch(this.type) {

      // Get the value from the other checkables.  Null values are converted
      // to empty strings to fix issues like with "visible".
      case 'radio': return this.$input.filter('[value="'+(content||'')+'"]').parent().text();

      // Wrap in container with special class
      case 'wysiwyg': return '<div class="wysiwyg">'+content+'</div>';

      // Format date
      case 'date': return content.replace(/(\d+)\-(\d+)\-(\d+)/, "$2/$3/$1");

      // Make an image tag
      case 'image':  return '<img src="'+content+'" class="image"/>';

      // Make a PATH from the slug
      case 'slug': return this.$input.siblings('.input-group-addon').text()+content;

      // Locales shouldn't be shown
      case 'locale': return null;

      // Don't massage
      case 'text':
      default: return content;
    }
  };

  // Hide the popover
  View.hide = function() {
    this.$el.popover('destroy');
  };

  // Return view class
  return Backbone.View.extend(View);
});
