function Picker() {

   Picker.prototype.Attach = function(Options) {

      // Load options from supplied options object
      this.Options = Options;
      this.RangeTarget = jQuery(Options.Range);
      this.Graduations = Options.MaxGraduations || 8;
      this.MaxSelection = Options.MaxSelection || 0;
      this.MaxPageSize = Options.MaxPageSize || 0;
      this.Nudge = Options.Nudge || true;

      this.RangeTarget.after('<a class="RangeToggle" href="#">' + this.RangeTarget.val() + '</a>');
      this.RangeTarget.hide();
      this.RangeToggle = jQuery('a.RangeToggle');
      this.RangeTarget.bind('change', function() {
         jQuery('a.RangeToggle').text(jQuery(this).val())
      })

      this.PickerContainer = jQuery('div.Picker');
      // Add the picker container if it wasn't already on the page somewhere
      if (this.PickerContainer.length == 0) {
         this.RangeToggle.after('<div class="Picker"></div>');
         this.PickerContainer = jQuery('div.Picker');
      }
      this.PickerContainer.hide();
      this.PickerContainer.html(this.Settings.SliderHtml);
      this.RangeToggle.bind('click', jQuery.proxy(function(e) {
         if (jQuery(e.target).hasClass('RangeToggleActive')) {
            jQuery(e.target).removeClass('RangeToggleActive');
            this.PickerContainer.slideUp('fast');
         } else {
            jQuery(e.target).addClass('RangeToggleActive');
            this.PickerContainer.slideDown('fast');
            this.UpdateUI();
            this.SyncSlider();
         }
         jQuery(e.target).blur();
      },this));

      this.DownTarget = false;
      this.SlideRail = jQuery('div.Slider');
      this.Slider = jQuery('div.SelectedRange');

      this.HandleStart = jQuery('div.HandleStart');
      this.HandleEnd = jQuery('div.HandleEnd');
      this.Range = jQuery('div.SelectedRange');
      this.InputStart = jQuery('div.InputRange input[name=DateStart]');
      this.InputEnd = jQuery('div.InputRange input[name=DateEnd]');

      this.RailWidth = (this.SlideRail.width() != 0) ? this.SlideRail.width() : 700;

      this.HandleStart.get(0).limit = jQuery.proxy(function(){
         return this.LimitStayLeft(this.HandleEnd);
      }, this);

      this.HandleEnd.get(0).limit = jQuery.proxy(function(){
         return this.LimitStayRight(this.HandleStart);
      }, this);

      jQuery('div.Slider').bind('mousemove', jQuery.proxy(this.MoveDelegator, this));

      jQuery('div.SliderHandle, div.SelectedRange').bind('mousedown', jQuery.proxy(function(e){
         this.Down(e.clientX);
         var OffsetL = this.DownRailX - this.Slider.position().left;
         var OffsetR = (this.Slider.position().left + this.Slider.width()) - this.DownRailX;
         var VerticalConstrained = (e.clientY >= this.Slider.offset().top && e.clientY <= (this.Slider.offset().top + this.Slider.height())) ? true : false;
         if (jQuery(e.target).hasClass('SliderHandle') && (VerticalConstrained && (OffsetL > 10 && OffsetR > 10)))
            e.target = jQuery('div.SelectedRange');

         this.DownTarget = jQuery(e.target);
         this.DownMoveHandler = (this.DownTarget.hasClass('SelectedRange')) ? this.MoveSlider : this.MoveHandle;
         return false;
      },this));

      jQuery(document).bind('mouseup', jQuery.proxy(function(e){
         if (this.DownTarget == false) return;
         this.UpdateUI(true);
         this.DownTarget = false;
         this.DownMoveHandler = false;
         return false;
      },this));

      jQuery('div.InputRange input').bind('change', jQuery.proxy(function(e){
         this.SetRange(this.InputStart.val(), this.InputEnd.val(), true);
      },this));

      this.ConfigureRail(Options.DateStart, Options.DateEnd, Options.Units);

   }

   Picker.prototype.Down = function(ClientX) {
      this.DownX = ClientX;

      this.DownSliderWidth = this.Slider.width();
      this.DownRailWidth = this.SlideRail.width();

      this.DownRailLeft = this.SlideRail.offset().left;
      this.DownRailRight = this.DownRailLeft + this.DownRailWidth;
      this.DownRailX = this.DownX - this.DownRailLeft;

      this.DownClickLeftDifference = this.DownX - this.Slider.offset().left;
      this.DownClickRightDifference = (this.Slider.offset().left + this.DownSliderWidth) - this.DownX;
   }

   Picker.prototype.UpdateUI = function(HardUpdate, NoTrigger) {
      this.RailWidth = this.SlideRail.width();

      var StartPerc = this.HandleStart.position().left;
      if (String(this.HandleStart.css('left')).substring(-1,1) != '%')
         StartPerc = StartPerc / this.RailWidth;

      var StartDeltaMilli = StartPerc * this.Axis.Diff.Milli;
      var StartDate = this.GetStartLimit(new Date(this.Axis.Start.Date.valueOf() + StartDeltaMilli),this.Units);
      var StartShortDate = this.GetStrDate(StartDate);
      this.HandleStart.html(StartShortDate);
      this.InputStart.val(StartShortDate);

      var EndPerc = this.HandleEnd.position().left;
      if (String(this.HandleEnd.css('left')).substring(-1,1) != '%')
         EndPerc = EndPerc / this.RailWidth;

      var EndDeltaMilli = EndPerc * this.Axis.Diff.Milli;
      var EndDate = this.GetEndLimit(new Date(this.Axis.Start.Date.valueOf() + EndDeltaMilli),this.Units);
      var EndShortDate = this.GetStrDate(EndDate);
      this.HandleEnd.html(EndShortDate);
      this.InputEnd.val(EndShortDate);

      if (HardUpdate == true) {
         var FormatStartDate = this.GetLongDate(StartDate);
         var FormatEndDate = this.GetLongDate(EndDate);
         this.RangeTarget.val(FormatStartDate+' - '+FormatEndDate);
         if (!NoTrigger)
            this.RangeTarget.trigger('change');
      }
   }

   Picker.prototype.SetRange = function(RangeStart, RangeEnd, Trigger, SetAnchor) {
      if (Date.parse(RangeStart) < 1 || Date.parse(RangeEnd) < 1) return;

      RangeStart = this.NewDate(RangeStart);
      RangeEnd = this.NewDate(RangeEnd);

      if (RangeStart.valueOf() < this.Axis.Start.Milli)
         RangeStart.setTime(this.Axis.Start.Milli);

      if (RangeEnd.valueOf() > this.Axis.End.Milli)
         RangeEnd.setTime(this.Axis.End.Milli);

      var DateRangeStart = this.GetStartLimit(RangeStart, this.Units);
      var DateRangeEnd = this.GetEndLimit(RangeEnd, this.Units);

      var FormatStartDate = this.GetLongDate(DateRangeStart);
      var FormatEndDate = this.GetLongDate(DateRangeEnd);
      this.RangeTarget.val(FormatStartDate+' - '+FormatEndDate);

      var MilliStartDiff = DateRangeStart.valueOf() - this.Axis.Start.Milli;
      var MilliEndDiff = DateRangeEnd.valueOf() - this.Axis.Start.Milli;

      var PercStart = (MilliStartDiff / this.Axis.Diff.Milli) * 100;
      var PercEnd = (MilliEndDiff / this.Axis.Diff.Milli) * 100;

      this.DoMoveHandle(this.HandleStart, PercStart, false, RangeStart);
      this.DoMoveHandle(this.HandleEnd, PercEnd, false, RangeEnd);

      this.SyncSlider();

      if (Trigger == true) {
         this.RangeTarget.trigger('change');
      }

      if (SetAnchor == true) {
         jQuery('a.RangeToggle').html(FormatStartDate+' - '+FormatEndDate);
      }
   }

   Picker.prototype.MoveDelegator = function(e) {
      if (this.DownTarget == false) return;
      this.DownMoveHandler(this.DownTarget, e);
      this.UpdateUI();
      return false;
   }

   Picker.prototype.MoveHandle = function(Handle, Event) {

      // Computed 'X' relative to the start of the slide rail
      var RelativeX = Event.clientX - this.DownRailLeft;
      RelativeX = (RelativeX < 0) ? 0 : RelativeX;
      RelativeX = (RelativeX > this.DownRailWidth) ? this.DownRailWidth : RelativeX;
      var PercX = (RelativeX / this.DownRailWidth) * 100;

      var MoveAction = this.DoMoveHandle(Handle, PercX);
      if (MoveAction.Moved != 0)
         this.SyncSlider();

      return MoveAction.Moved;
   }

   Picker.prototype.SyncSlider = function() {
      var LeftPerc = this.ToPerc(this.HandleStart.css('left'));
      var RightPerc = this.ToPerc(this.HandleEnd.css('left'));
      var PercDiff = RightPerc - LeftPerc;

      jQuery(this.Range).css('left',LeftPerc+'%');
      jQuery(this.Range).css('width',PercDiff+'%');
   }

   Picker.prototype.DoMoveHandle = function(Handle, ProposedPercX, Manual, SetDate) {
      if (Manual != true && this.Nudge == true) {
         var AllowedMinMax = Handle.get(0).limit();
         if (ProposedPercX > AllowedMinMax.right || ProposedPercX < AllowedMinMax.left) {
            // Nudge
            this.DoMoveHandle(AllowedMinMax.Ref, ProposedPercX);
         }
      }

      var AllowedMinMax = Handle.get(0).limit();
      var RealPercX = ProposedPercX;
      RealPercX = (RealPercX < AllowedMinMax.left) ? AllowedMinMax.left : RealPercX;
      RealPercX = (RealPercX > AllowedMinMax.right) ? AllowedMinMax.right : RealPercX;
      var CurrentPercX = this.ToPerc(jQuery(Handle).css('left'));
      jQuery(Handle).css('left',RealPercX+'%');

      if (SetDate != undefined) {
         Handle.html(this.GetStrDate(SetDate));
      }

      return {
         'Ref': AllowedMinMax.Ref,
         'Moved': RealPercX - CurrentPercX
      }
   }

   Picker.prototype.MoveSlider = function(Handle, Event) {
      var LeftRelativeX = (Event.clientX - this.DownRailLeft) - this.DownClickLeftDifference;
      if (LeftRelativeX < 0) {
         LeftRelativeX = 0;
      }
      var LeftPercX = (LeftRelativeX / this.DownRailWidth) * 100;

      var RightRelativeX = LeftRelativeX + this.DownSliderWidth;
      if (RightRelativeX > this.DownRailWidth) {
         RightRelativeX = this.DownRailWidth;
         LeftRelativeX = RightRelativeX - this.DownSliderWidth;
         LeftPercX = (LeftRelativeX / this.DownRailWidth) * 100;
      }
      var RightPercX = (RightRelativeX / this.DownRailWidth) * 100;

      MoveAction = this.DoMoveHandle(this.HandleStart, LeftPercX, true);
      MoveAction = this.DoMoveHandle(this.HandleEnd, RightPercX, true);

      this.Range.css('left',LeftPercX+'%');
   }

   Picker.prototype.LimitStayLeft = function(ReferenceElement) {
      return { 'left':0, 'right':this.ToPerc(ReferenceElement.css('left')), 'Ref':ReferenceElement };
   }

   Picker.prototype.LimitStayRight = function(ReferenceElement) {
      return { 'left':this.ToPerc(ReferenceElement.css('left')), 'right':100, 'Ref':ReferenceElement };
   }

   Picker.prototype.ToPerc = function(X) {
      var ItemString = String(X); 
      var ItemLength = ItemString.length;
      var LastChar = ItemString.substring(ItemLength-1, ItemLength);

      if (LastChar == '%') return parseFloat(X);
      return (parseFloat(X) / parseFloat(this.SlideRail.width())) * 100.0;
   }

   Picker.prototype.ConfigureRail = function(StartDate, EndDate, Units) {
      this.Units = Units;

      var AdjustedStartLimit = this.GetStartLimit(StartDate, this.Units);
      var AdjustedEndLimit = this.GetEndLimit(EndDate, this.Units);

      this.Rail = {
         'Start': {'Original':StartDate, 'Date':AdjustedStartLimit, 'Milli': AdjustedStartLimit.valueOf()},
         'End': {'Original':EndDate, 'Date':AdjustedEndLimit, 'Milli': AdjustedEndLimit.valueOf()},
         'Diff': {},
         'Pages': []
      };

      this.Rail.Diff.Milli = this.Rail.End.Date.getTime() - this.Rail.Start.Date.getTime();
      this.Rail.Diff.Sec = this.Rail.Diff.Milli / 1000;
      this.Rail.Diff.Day = this.Rail.Diff.Sec / (3600*24);

      if (this.MaxPageSize == -1) { // JS decides
         switch (this.Units) {
            case 'month': this.MaxPageSize = 0; break;
            case 'week': this.MaxPageSize = 52; break;
            case 'day': this.MaxPageSize = 60; break;
         }
      }

      if (this.MaxPageSize > 0) {
         var Increment = 0;
         var WorkingTick = this.NewDate(this.Rail.End.Date);
         var AnchorTick = this.NewDate(this.Rail.End.Date);

         do {
            var IterateTick = this.GetPrecedingTick(WorkingTick);

            if (IterateTick !== false) 
               WorkingTick = IterateTick;

            Increment++;
            if (Increment % this.MaxPageSize == 0) {
               //Increment = 0;
               this.AddRailPage(WorkingTick, AnchorTick);
               AnchorTick = this.NewDate(WorkingTick);
            }

         } while (IterateTick !== false);

         // Catch remainder
         if (Increment)
            this.AddRailPage(WorkingTick, AnchorTick);
      } else {
         this.AddRailPage(AdjustedStartLimit, AdjustedEndLimit);
      }

      this.RailPager = jQuery('div.Picker div.SlidePager');
      if (this.Rail.Pages.length > 1) {
         // Do Pagination
         this.RailPager.show();

         this.RailPager.find('div.SlidePage').bind('click', jQuery.proxy(function(e){
            if (jQuery(e.target).hasClass('PageBack'))
               this.SetRailPage(this.Rail.Page+1);
            else
               this.SetRailPage(this.Rail.Page-1);

            return false;
         },this));
      }

      this.SetRailPage(1, true);

      var RangeStart = this.Options.RangeStart || this.Options.DateStart;
      var RangeEnd = this.Options.RangeEnd || this.Options.DateEnd;

      this.SetRange(RangeStart, RangeEnd, false, true);
   }

   Picker.prototype.AddRailPage = function(StartDate, EndDate) {
      this.Rail.Pages.push({'Start':this.NewDate(StartDate), 'End':this.NewDate(EndDate)});
   }

   Picker.prototype.SetRailPage = function(PageNumber, NoAutoUpdate) {
      var PageIndex = PageNumber - 1;

      if (PageIndex < 0 || PageIndex >= this.Rail.Pages.length) return;

      var Page = this.Rail.Pages[PageIndex];
      this.Rail.Page = PageNumber;
      this.SetAxis(Page.Start, Page.End);

      if (PageIndex == 0)
         this.RailPager.find('div.PageForward').addClass('CannotPageForward');
      else
         this.RailPager.find('div.PageForward').removeClass('CannotPageForward');

      if (PageNumber >= this.Rail.Pages.length)
         this.RailPager.find('div.PageBack').addClass('CannotPageBack');
      else
         this.RailPager.find('div.PageBack').removeClass('CannotPageBack');

      if (NoAutoUpdate != true)
         this.UpdateUI(true);

   }

   Picker.prototype.SetAxis = function(StartDate, EndDate) {
      var AdjustedStartLimit = this.GetStartLimit(StartDate, this.Units);
      var AdjustedEndLimit = this.GetEndLimit(EndDate, this.Units);

      this.Axis = {
         'Start': {'Original':StartDate, 'Date':AdjustedStartLimit, 'Milli': AdjustedStartLimit.valueOf()},
         'End': {'Original':EndDate, 'Date':AdjustedEndLimit, 'Milli': AdjustedEndLimit.valueOf()},
         'Diff': {},
         'Ticks': {}
      };

      var MilliDiff = this.Axis.Diff.Milli = this.Axis.End.Date.getTime() - this.Axis.Start.Date.getTime();
      var SecondsDiff = this.Axis.Diff.Sec = MilliDiff / 1000;
      var DaysDiff = this.Axis.Diff.Day = SecondsDiff / (3600*24);

      switch (this.Units) {
         case 'month':
            var NumTicks = 0; var MonthTicks = [];
            var WorkingDate = this.NewDate(this.Axis.Start.Date);
            do {
               var TickLabel = this.GetShortMonth(WorkingDate.getMonth())+' \''+String(WorkingDate.getFullYear()).substring(2,4);

               var NextMonth = (WorkingDate.getMonth() < 11) ? WorkingDate.getMonth()+1 : 0;
               var NextYear = (WorkingDate.getMonth() < 11) ? WorkingDate.getFullYear() : WorkingDate.getFullYear()+1;
               WorkingDate.setFullYear(NextYear);
               WorkingDate.setMonth(NextMonth);

               var KeepItUp = ((WorkingDate.getFullYear() > this.Axis.End.Date.getFullYear()) || (WorkingDate.getFullYear() == this.Axis.End.Date.getFullYear()) && (WorkingDate.getMonth() > this.Axis.End.Date.getMonth())) ? false : true;
               if (KeepItUp) {
                  MonthTicks[NumTicks] = TickLabel;
                  NumTicks++;
               }
            } while(KeepItUp == true);

         break;
         case 'week':
            var NumTicks = DaysDiff/7;
         break;
         case 'day':
            var NumTicks = DaysDiff;
         break;
      }

      this.Axis.Ticks.Count = NumTicks;
      this.Axis.Ticks.PerGraduation = Math.ceil(NumTicks / this.Graduations);
      this.Graduations = NumTicks / this.Axis.Ticks.PerGraduation;
      var WidthPerTick = this.RailWidth / NumTicks;
      this.Axis.Ticks.WidthPerGraduation = this.Axis.Ticks.PerGraduation * WidthPerTick;

      var SliderDates = jQuery('div.SliderDates');
      SliderDates.html('');
      var SliderWidth = (this.RailWidth / this.Graduations) - 20;
      for (Graduation = 0; Graduation < this.Graduations; Graduation++) {
         var Tick = Graduation * this.Axis.Ticks.PerGraduation;
         var AmountPercent = (Tick / NumTicks);
         var DeltaMilli = AmountPercent * MilliDiff;
         var SpotDate = new Date(this.Axis.Start.Date.valueOf() + DeltaMilli);

         if (this.Units == 'month') {
            var TickLabel = MonthTicks[Tick];
         } else {
            var TickLabel = this.GetShortMonth(SpotDate.getMonth())+' '+SpotDate.getDate();
         }

         var PxLeft = (AmountPercent * this.RailWidth);
         SliderDates.append('<div class="SliderDate" style="left: '+PxLeft+'px;">'+TickLabel+'</div>');
      }

   }

   Picker.prototype.GetLongDate = function(DateItem) {
      return this.GetMonth(DateItem.getMonth())+' '+DateItem.getDate()+', '+DateItem.getFullYear();
   }

   Picker.prototype.GetStrDate = function(DateItem) {
      return (DateItem.getMonth()+1)+'/'+DateItem.getDate()+'/'+DateItem.getFullYear();
   }

   Picker.prototype.GetShortStrDate = function(DateItem) {
      return DateItem.getDate()+'/'+(DateItem.getMonth()+1)+'/'+String(DateItem.getFullYear()).substring(2,4);
   }

   Picker.prototype.GetPrecedingTick = function(DateObj) {
      switch (this.Units) {
         case 'month':
            DateObj.setMonth(DateObj.getMonth()-1);
         break;
         case 'week':
            DateObj.setDate(DateObj.getDate()-7);
         break;
         case 'day':
            DateObj.setDate(DateObj.getDate()-1);
         break;
      }      
      if (DateObj.valueOf() < this.Rail.Start.Date.valueOf())
         return false;
      return DateObj;
   }

   Picker.prototype.GetStartLimit = function(DateItem, Unit) {

      var CurrentDate = this.NewDate(DateItem);

      switch(Unit) {
         case 'month':
            CurrentDate.setDate(1);
            return CurrentDate;
         break;
         case 'week':
            if (CurrentDate.getDate() > CurrentDate.getDay()) {
               CurrentDate.setDate(CurrentDate.getDate() - CurrentDate.getDay());
            } else {
               var Difference = CurrentDate.getDay() - CurrentDate.getDate();

               // Gotta roll back to previous month. Gotta check if possible first, otherwise roll back the year too.
               if (CurrentDate.getMonth()) {
                  CurrentDate.setMonth(CurrentDate.getMonth()-1);
               } else {
                  CurrentDate.setYear(CurrentDate.getYear()-1);
                  CurrentDate.setMonth(11);
               }
               var DaysInMonth = this.GetDaysInMonth(CurrentDate.getYear(), CurrentDate.getMonth());
               CurrentDate.setDate(DaysInMonth-Difference);
            }

            return CurrentDate;
         break;
         case 'day':
         default:
            return CurrentDate;
         break;
      }
   }

   Picker.prototype.GetEndLimit = function(DateItem, Unit) {
      return this.GetStartLimit(DateItem, Unit);
   }

   Picker.prototype.NewDate = function(DateItem) {
      var DateType = gettype(DateItem);

      CurrentDate = false;      
      if (DateType == 'string') {
         var CurrentDate = new Date();

         var dateRegex1 = /(\d{1,2})\/(\d{1,2})\/(\d{4})/;
         var dateMatch = dateRegex1.exec(DateItem);
         if (dateMatch != null) {
            CurrentDate.setMonth(parseInt(dateMatch[1], 10) - 1);
            CurrentDate.setDate(dateMatch[2]);
            CurrentDate.setFullYear(dateMatch[3]);
         } else {
            var DatePart = DateItem.split(' ').shift();
            var DateParts = DatePart.split('-');

            // Retardo month indexing from 0
            DateParts[1] = parseInt(DateParts[1], 10) - 1;
            CurrentDate.setFullYear(DateParts[0]);
            CurrentDate.setMonth(DateParts[1]);
            CurrentDate.setDate(DateParts[2]);
         }
      }
      else {
         return new Date(DateItem);
      }
      return CurrentDate;
   }

   Picker.prototype.GetDaysInMonth = function (Year, Month) {
      Month++;
      switch (Month) {
         case 1: return 31;
         case 2:
            var isLeap = new Date(Year,1,29).getDate() == 29;
            return (isLeap) ? 29 : 28;
         case 3: return 31;
         case 4: return 30;
         case 5: return 31;
         case 6: return 30;
         case 7: return 31;
         case 8: return 31;
         case 9: return 30;
         case 10: return 31;
         case 11: return 30;
         case 12: return 31;
      }
   }

   Picker.prototype.GetShortMonth = function(Month) {
      var M = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
      return M[Month];
   }

   Picker.prototype.GetMonth = function(Month) {
      var M = ['January','February','March','April','May','June','July','August','September','October','November','December'];
      return M[Month];
   }

   Picker.prototype.Settings = {
      SliderHtml: '\
<div class="Slider"> \
   <div class="SlidePager"> \
      <div class="SlidePage PageBack"><span>&laquo;</span></div> \
      <div class="SlidePage PageForward"><span>&raquo;</span></div> \
   </div> \
   <div class="SelectedRange"></div> \
   <div class="HandleContainer"> \
      <div class="SliderHandle HandleStart"></div> \
      <div class="SliderHandle HandleEnd"></div> \
   </div> \
   <div class="Range RangeStart"></div><div class="Range RangeMid"></div><div class="Range RangeEnd"></div> \
   <div class="SliderDates"></div> \
</div> \
<hr /> \
<div class="InputRange"> \
   <label for="DateStart" class="DateStart">Start Date</label> \
   <input type="text" name="DateStart" /> \
   <label for="DateEnd" class="DateEnd">End Date</label> \
   <input type="text" name="DateEnd" /> \
</div> \
'
   }

}

function gettype (mixed_var) {
   // http://kevin.vanzonneveld.net
   // +   original by: Paulo Freitas
   // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
   // +   improved by: Douglas Crockford (http://javascript.crockford.com)
   // +   input by: KELAN
   // +   improved by: Brett Zamir (http://brett-zamir.me)
   // -    depends on: is_float
   // %        note 1: 1.0 is simplified to 1 before it can be accessed by the function, this makes
   // %        note 1: it different from the PHP implementation. We can't fix this unfortunately.
   // *     example 1: gettype(1);
   // *     returns 1: 'integer'
   // *     example 2: gettype(undefined);
   // *     returns 2: 'undefined'
   // *     example 3: gettype({0: 'Kevin van Zonneveld'});
   // *     returns 3: 'array'
   // *     example 4: gettype('foo');
   // *     returns 4: 'string'
   // *     example 5: gettype({0: function () {return false;}});
   // *     returns 5: 'array'

   var s = typeof mixed_var, name;
   var getFuncName = function (fn) {
      var name = (/\W*function\s+([\w\jQuery]+)\s*\(/).exec(fn);
      if (!name) {
            return '(Anonymous)';
      }
      return name[1];
   };
   if (s === 'object') {
      if (mixed_var !== null) { // From: http://javascript.crockford.com/remedial.html
            if (typeof mixed_var.length === 'number' &&
                  !(mixed_var.propertyIsEnumerable('length')) &&
                  typeof mixed_var.splice === 'function') {
               s = 'array';
            }
            else if (mixed_var.constructor && getFuncName(mixed_var.constructor)) {
               name = getFuncName(mixed_var.constructor);
               if (name === 'Date') {
                  s = 'date'; // not in PHP
               }
               else if (name === 'RegExp') {
                  s = 'regexp'; // not in PHP
               }
               else if (name === 'PHPJS_Resource') { // Check against our own resource constructor
                  s = 'resource';
               }
            }
      } else {
            s = 'null';
      }
   }
   else if (s === 'number') {
      s = this.is_float(mixed_var) ? 'double' : 'integer';
   }
   return s;
}