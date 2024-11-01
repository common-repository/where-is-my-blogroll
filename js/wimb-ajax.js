(function($) {

  if( $('#wimb-rss-feed').length ) {
    $(function() {
      new WhereIsMyBlogroll();
    });
  }

  class WhereIsMyBlogroll {
    constructor() {
      var self = this;
      self.load_widget();
    }

    load_widget() {
      var self = this;

      self.ajax_data = {}
      self.container = $('#wimb-rss-feed');
      self.loader = $(self.container).data('loading-text');
      self.ajax_data['action'] = 'wimb_dashboard_widget_ajax_request';

      $(self.container).html(self.loader);

      self.ajax_request(self.ajax_data).done(function(response) {
        $(self.container).html(response);
      });
    }

    ajax_request(data) {
      return $.ajax({
    		url : wimb_ajax.ajax_url,
        data : data,
    	});
    }
  }

})(jQuery)
