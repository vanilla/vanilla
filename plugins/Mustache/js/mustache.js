
/**
 * @copyright 2003 Vanilla Forums, Inc
 * @license Proprietary
 */

/**
 * Mustache template renderer
 *
 * @type type
 */
gdn.mustache = {

   driver: 'Mustache',
   templates: {},
   background: false,

   /**
    * Register a group of templates
    *
    * @param array template An array of Template objects suitable for
    * Template::RegisterTemplate().
    */
   tegister: function(templates) {
      jQuery.each(templates, function(i, template){
         gdn.mustache.registerTemplate(template);
      });
   },

   /**
    * Register a Template object
    *
    * Template is a JSON object with the following keys:
    *
    *  Name       // Name of the template as it is referred to on the serverside
    *  URL        // String Mustache Template contents
    *  Type       // Type of template
    *  Contents   // Optional. Contents of the template.
    *
    * @param Object template Template object
    */
   registerTemplate: function(template) {
      gdn.mustache.templates[template.name] = template;
      gdn.mustache.templates[template.name].downloading = false;

      // Background download templates
      if (gdn.mustache.background && template.type === 'defer') {
         gdn.mustache.download(template.name);
     }
   },

   /**
    * Render the supplied Template and View
    *
    * Renders the Template with View as the data, then returns the resulting
    * string.
    *
    * @param string templateName Name of the template to render
    * @param Object view View object for Mustache
    * @param array partials A list of partial templates to include
    */
   render: function(templateName, view, partials) {
      var templateSrc = gdn.mustache.getTemplateSrc(templateName);
      if (templateSrc) {
         partials = gdn.mustache.getPartials(partials);
         return Mustache.render(templateSrc, view, partials);
      } else {
         return '';
      }
   },

   /**
    * Render Template+View and replace Element with result
    *
    * Performs a normal Template Render with the supplied Template and View, then
    * replaces the supplied Element with the resulting string.
    *
    * @param string template Name of the template to render
    * @param Object view View object for Mustache
    * @param DOMObject element DOM object to replace
    * @param array partials A list of partial templates to include
    */
   renderInPlace: function(templateName, view, element, partials) {
      var render = gdn.mustache.render(templateName, view, partials);
      var replace = jQuery(render);
      jQuery(element).replaceWith(replace);
      return replace;
   },

   /**
    * Update the given template
    *
    * @param string templateName
    * @param string field
    * @param mixed value
    */
   setTemplateField: function(templateName, field, value) {
      if (!gdn.mustache.templates.hasOwnProperty(templateName)) {
          return false;
      }
      gdn.mustache.templates[templateName][field] = value;
   },

   /**
    * Get a template object
    *
    * @param string templateName
    */
   getTemplate: function(templateName) {
      if (!gdn.mustache.templates.hasOwnProperty(templateName)) {
          return false;
      }
      return gdn.mustache.templates[templateName];
   },

   /**
    * Get the string contents of the given template name
    *
    * @param string template Name of the template to retrieve
    */
   getTemplateSrc: function(templateName) {
      var template = gdn.mustache.getTemplate(templateName);
      if (!template) {
          return false;
      }

      if (!template.hasOwnProperty('contents')) {
         gdn.mustache.download(templateName, 'sync');
         template = gdn.mustache.getTemplate(templateName);
      }

      return template.Contents;

   },

   getPartials: function(partials) {
      var partialList = {};
      if (partials instanceof Array) {
         jQuery.each(partials, function(i, partialName){
            var partialTemplate = gdn.mustache.getTemplateSrc(partialName);
            if (!partialTemplate) {
                return;
            }

            partialList[partialName] = partialTemplate;
         });
      }
      return partialList;
   },

   download: function(templateName, mode) {
      var template = gdn.mustache.getTemplate(templateName);

      // We're downloading, don't queue another download
      if (template.downloading) {
          return null;
      }
      gdn.mustache.setTemplateField(templateName, 'downloading', true);

      var async = false;
      switch (mode) {
         case 'sync':
            async = false;
            break;

         case 'async':
         default:
            async = true;
            break;
      }
      var templateURL = template.url;
      jQuery.ajax({
         url: templateURL,
         async: async,
         dataType: 'html',
         success: function(data, status, xhr) {

            template.contents = data;
            gdn.mustache.templateLoaded(template)

         },
         error: function(xhr, status, error) {}
      });
   },

   templateLoaded: function(template) {
      // Store template contents
      gdn.mustache.setTemplateField(template.name, 'contents', template.contents);
      gdn.mustache.setTemplateField(template.name, 'downloading', false);
   }

};