var Gdn_Slices = {

   Load: function() {
      var SliceUniq = Math.floor(Math.random() * 9999999);
      var NextSliceID = SliceUniq+1;
   
      var Candidates = $('.Slice');
      var Slices = [];
      Candidates.each(jQuery.proxy(function(i,Slice) {
         var MySlice = new Gdn_Slice(Slice, NextSliceID++);
         Slices.push(MySlice);
         MySlice.Go();
      },this));
   }

};

function Gdn_Slice(SliceElement, SliceID) {

   this.Slice = $(SliceElement);
   this.RawSlice = SliceElement;
   this.Slice.css('position','relative');
   
   this.SliceID = SliceID;
   this.RawSlice.SliceID = this.SliceID;
   
   Gdn_Slice.prototype.Go = function() {
      this.RawSlice.Slice = this;
      
      if (this.Slice.hasClass('Async'))
         this.GetSlice();
      else
         this.ParseSlice();
   }
   
   Gdn_Slice.prototype.PrepareSliceForRequest = function() {
      var SliceDimensions = {
         'width': this.Slice.width(),
         'height': this.Slice.height()
      };
      
      if (!SliceDimensions.height) {
         this.Slice.css('height', '30px');
         SliceDimensions.height = 30;
      }
         
/*
      if (!SliceDimensions.width) {
         this.Slice.css('width', '300px');
         SliceDimensions.width = 300;
      }
*/

      var Overlay = document.createElement('div');
      Overlay.className = 'SliceOverlay';
      $(Overlay).css({
         'position': 'absolute',
         'top': '0px',
         'left': '0px',
         'background-color': '#DBF3FC',
         'width': SliceDimensions.width-30,
         'height': SliceDimensions.height+20,
         'color': '#222222',
         'line-height': SliceDimensions.height+'px',
         'font-size': '12px',
         'padding': '0px 15px',
         'opacity': 0
      });
      
      var ImgPath = gdn.definition('WebRoot')+"/applications/dashboard/design/images/progress_sm.gif";
      $(Overlay).html('<img src="'+ImgPath+'"/>');
      this.Slice.append(Overlay);
      $(Overlay).fadeTo('fast',0.7);
      this.Slice.animate({
         'padding': '10px'
      });
   }
   
   Gdn_Slice.prototype.GetSlice = function(PassiveGet) {
      if (PassiveGet !== true)
         this.SliceURL = this.Slice.attr('rel');
         
      this.PrepareSliceForRequest();
      
      var SliceURL = gdn.combinePaths(gdn.definition('WebRoot'),this.SliceURL);
      jQuery.ajax({
         url: SliceURL,
         type: 'GET',
         data: {'DeliveryType':'VIEW'},
         success: jQuery.proxy(this.GotSlice,this)
      });
   }
   
   Gdn_Slice.prototype.PostSlice = function() {
      this.PrepareSliceForRequest();
      
      var SliceURL = gdn.combinePaths(gdn.definition('WebRoot'),this.SliceURL+'?DeliveryType=VIEW');
      jQuery.ajax({
         url: SliceURL,
         type: 'POST',
         data: this.GetSliceData(),
         success: jQuery.proxy(this.GotSlice,this)
      });
   }
   
   Gdn_Slice.prototype.ReplaceSlice = function(NewSliceURL) {
      this.Slice.attr('rel', NewSliceURL);
      this.SliceURL = NewSliceURL;
      this.GetSlice(true);
   }
   
   Gdn_Slice.prototype.GotSlice = function(Data, Status, XHR) {
      this.Slice.animate({
         'padding': '0px'
      });
      var SliceHolder = document.createElement('div');
      $(SliceHolder).html(Data);
      
      // Configs
      var SliceConfig = $(SliceHolder).find('.Slice .SliceConfig');
      if (SliceConfig.length) {
         var SliceConfig = $.parseJSON(SliceConfig.html());
         $(SliceConfig.css).each(function(i,el){
            var v_css  = document.createElement('link');
         	v_css.rel = 'stylesheet'
         	v_css.type = 'text/css';
         	v_css.href = gdn.combinePaths(gdn.definition('WebRoot'),el);
         	document.getElementsByTagName('head')[0].appendChild(v_css);
         });
         
         $(SliceConfig.js).each(function(i,el){
            var v_js  = document.createElement('script');
         	v_js.type = 'text/javascript';
         	v_js.href = gdn.combinePaths(gdn.definition('WebRoot'),el);
         	document.getElementsByTagName('head')[0].appendChild(v_js);
         });
      }
      
      var SliceContents = $(SliceHolder).find('.Slice');
      this.Slice.find('.SliceOverlay').fadeTo('fast', 0,jQuery.proxy(function(){
         this.Slice.css({
            'height': '',
            'width': ''
         });
         this.Slice.html(SliceContents.html());
         this.ParseSlice();
      },this));
   }
   
   Gdn_Slice.prototype.ParseSlice = function() {
      this.SliceURL = this.Slice.attr('rel');
      
      this.SliceFields = [];
      this.Slice.find('form').submit(function() { return false; });
      this.Slice.find('input').each(jQuery.proxy(function(i,Input){
         this.SliceFields.push(Input);
         if ($(Input).hasClass('SliceSubmit'))
            $(Input).one('click',jQuery.proxy(this.PostSlice,this));
      },this));
   }
   
   Gdn_Slice.prototype.GetSliceData = function() {
      var SubmitData = {};
      $(this.SliceFields).each(jQuery.proxy(function(i,Field){
         Field = $(Field);
         SubmitData[Field.attr('name')] = Field.val();
      },this));
      return SubmitData;
   }

}

$(document).ready(function(){
   Gdn_Slices.Load();
});