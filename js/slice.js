var Gdn_Slices = {

   Prepare: function() {
      Gdn_Slices.SliceUniq = Math.floor(Math.random() * 9999999);
      Gdn_Slices.Slices = [];
      
      Gdn_Slices.Load();
   },

   Load: function(Root) {
      if (Root != undefined) {
         var Candidates = Root.find('.Slice');
      } else {
         var Candidates = $('.Slice');
      }
      
      Candidates.each(jQuery.proxy(function(i,Slice) {
         var NextSliceID = Gdn_Slices.SliceUniq++;
         $(Slice).attr('slice', NextSliceID);
         var MySlice = new Gdn_Slice(Slice, NextSliceID);
         Gdn_Slices.Slices.push(MySlice);
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
      
      if (this.Slice.hasClass('Async')) {
         this.Slice.removeClass('Async');
         this.GetSlice();
      } else
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
   }
   
   Gdn_Slice.prototype.GetSlice = function(PassiveGet) {
      if (PassiveGet !== true)
         this.SliceURL = this.Slice.attr('rel');
         
      this.PrepareSliceForRequest();
      
      var SliceURL = gdn.url(this.SliceURL);
      jQuery.ajax({
         url: SliceURL,
         type: 'GET',
         data: {'DeliveryType':'VIEW'},
         success: jQuery.proxy(this.GotSlice,this)
      });
   }
   
   Gdn_Slice.prototype.PostSlice = function(Event) {
      this.PrepareSliceForRequest();
      
      var SliceForm = $(Event.target).parents('form').first();
      
      
      if (this.SliceForm) {
         if ($(SliceForm).attr('jsaction'))
            var SliceURL = $(SliceForm).attr('jsaction');
         else
            var SliceURL = $(SliceForm).attr('action');
      } else {
         var SliceURL = this.SliceURL;
      }
      
      SliceURL = gdn.url(SliceURL);
      
      jQuery.ajax({
         url: SliceURL,
         type: 'POST',
         data: this.GetSliceData(SliceForm),
         success: jQuery.proxy(this.GotSlice,this)
      });
      return false;
   }
   
   Gdn_Slice.prototype.ReplaceSlice = function(NewSliceURL) {
      this.Slice.attr('rel', NewSliceURL);
      this.SliceURL = NewSliceURL;
      this.GetSlice(true);
   }
   
   Gdn_Slice.prototype.GotSlice = function(Data, Status, XHR) {
   
      var DataObj = $(Data);
      if (!DataObj.find('.Slice').length && !DataObj.hasClass('Slice')) {
         // The slice isn't wrapped in anything so just put it inside the existing slice div.
         var SliceWrap = this.Slice.clone().empty().append(DataObj);
         DataObj = SliceWrap;
      }
   
      this.Slice.find('.SliceOverlay').fadeTo('fast', 0,jQuery.proxy(function(){
         this.Slice.css({
            'height': '',
            'width': ''
         });
         
         this.Slice.html('');
         for (var i = 0; i < DataObj.length; i++) {
            
            if (DataObj[i].tagName !== 'SCRIPT') {
               this.Slice.append($(DataObj[i]).html());
            } else {
               eval($(DataObj[i]).text());
            }
         }
         
         var SliceConfig = this.Slice.find('.SliceConfig').first();
         if (SliceConfig.length) {
            SliceConfig = $.parseJSON(SliceConfig.html());
            $(SliceConfig.css).each(function(i,el){
               var v_css  = document.createElement('link');
               v_css.rel = 'stylesheet'
               v_css.type = 'text/css';
               v_css.href = gdn.url(el);
               document.getElementsByTagName('head')[0].appendChild(v_css);
            });
            
            $(SliceConfig.js).each(function(i,el){
               var v_js  = document.createElement('script');
               v_js.type = 'text/javascript';
               v_js.src = gdn.url(el);
               document.getElementsByTagName('head')[0].appendChild(v_js);
            });
         }
         
         this.ParseSlice();
      },this));
   }
   
   Gdn_Slice.prototype.ParseSlice = function() {
      this.SliceURL = this.Slice.attr('rel');
      
      this.Slice.find('input.SliceSubmit').each(jQuery.proxy(function(i,Input){
         if ($(Input).parents('.Slice').attr('slice') != this.SliceID) return;
         
         $(Input).one('click',jQuery.proxy(this.PostSlice,this));
         var SliceForm = $(Input).parents('form').first()[0];
         
         this.SliceForm = false;
         if ($(Input).hasClass('SliceForm'))
            this.SliceForm = true;
         
         SliceForm.SliceFields = [];
         $(SliceForm).find('input').each(jQuery.proxy(function(i,LoopedInput){
            SliceForm.SliceFields.push(LoopedInput);
         },this));
      },this));
      
      jQuery(document).trigger('SliceReady');
      
      // Load potential inner slices
      Gdn_Slices.Load(this.Slice);
   }
   
   Gdn_Slice.prototype.GetSliceData = function(SliceForm) {
      SliceForm = $(SliceForm).first()[0];
      var SubmitData = {'DeliveryType':'VIEW'};
      $(SliceForm.SliceFields).each(jQuery.proxy(function(i,Field){
         Field = $(Field);
         
         if (Field.attr('type').toLowerCase() == 'checkbox') {
            if (Field.attr('checked'))
               SubmitData[Field.attr('name')] = Field.val();
         } else {
            SubmitData[Field.attr('name')] = Field.val();
         }
      },this));
      return SubmitData;
   }
   
   Gdn_Slice.prototype.Log = function(Message) {
      console.log('[sid:'+this.SliceID+'] '+Message);
   }

}

$(document).ready(function(){
   Gdn_Slices.Prepare();
});