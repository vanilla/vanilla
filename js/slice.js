var Gdn_Slices = {

   Load: function() {
      var SliceUniq = Math.floor(Math.random() * 9999999);
      var NextSliceID = SliceUniq+1;
   
      var Candidates = $('.Slice');
      Candidates.each(jQuery.proxy(function(i,Slice) {
         new Gdn_Slice(Slice, NextSliceID++);
      },this));
   }

};

var Gdn_Slice = Class.create({

   init: function(SliceElement, SliceID) {
      this.Slice = $(SliceElement);
      this.Slice.css('position','relative');
      this.SliceID = SliceID;
      if (this.Slice.hasClass('Async'))
         this.GetSlice();
      else
         this.ParseSlice();
   },
   
   PrepareSliceForRequest: function() {
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
         'background-color': 'white',
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
   },
   
   GetSlice: function() {
      this.PrepareSliceForRequest();
      
      var SliceURL = this.Slice.attr('rel');
      jQuery.ajax({
         url: SliceURL,
         type: 'GET',
         data: {'DeliveryType':'VIEW'},
         success: jQuery.proxy(this.GotSlice,this)
      });
   },
   
   PostSlice: function() {
      this.PrepareSliceForRequest();
      
      var SliceURL = gdn.definition('WebRoot')+this.Slice.attr('rel')+'?DeliveryType=VIEW';
      jQuery.ajax({
         url: SliceURL,
         type: 'POST',
         data: this.GetSliceData(),
         success: jQuery.proxy(this.GotSlice,this)
      });
   },
   
   GotSlice: function(Data, Status, XHR) {
      this.Slice.animate({
         'padding': '0px'
      });
      var SliceHolder = document.createElement('div');
      $(SliceHolder).html(Data);
      var SliceContents = $(SliceHolder).find('.Slice');
      this.Slice.find('.SliceOverlay').fadeTo('fast', 0,jQuery.proxy(function(){
         this.Slice.css({
            'height': '',
            'width': ''
         });
         this.Slice.html(SliceContents.html());
         this.ParseSlice();
      },this));
   },
   
   ParseSlice: function() {
      this.SliceFields = [];
      this.Slice.find('form').submit(function() { return false; });
      this.Slice.find('input').each(jQuery.proxy(function(i,Input){
         this.SliceFields.push(Input);
         if ($(Input).hasClass('SliceSubmit'))
            $(Input).click(jQuery.proxy(this.PostSlice,this));
      },this));
   },
   
   GetSliceData: function() {
      var SubmitData = {};
      $(this.SliceFields).each(jQuery.proxy(function(i,Field){
         Field = $(Field);
         SubmitData[Field.attr('name')] = Field.val();
      },this));
      return SubmitData;
   }

});

$(document).ready(function(){
   Gdn_Slices.Load();
});