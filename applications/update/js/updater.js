function Gdn_Updater() {
   this.Words = [];
   
   Gdn_Updater.prototype.Prepare = function() {
   
      this.Frame = $('div.UpdateProgress');
      if (this.Frame.length) {
         this.Action = this.Frame.html();
         this.Frame.html('');
         
         try {
            var Tasks = jQuery.parseJSON(this.Action);
            
            this.BuildProgressBar();
            this.PreloadQueue(Tasks);
         } catch(err) {
            
         }
      }
   }
   
   Gdn_Updater.prototype.BuildProgressBar = function() {
      var Holder = document.createElement('div');
      $(Holder).addClass('ProgressContainer');
      $(Holder).css('width','400px');
      this.Holder = $(Holder);
      this.Frame.append(Holder);
      
      var Slider = document.createElement('div');
      $(Slider).addClass('ProgressSlider');
      this.Slider = $(Slider);
      $(Holder).append(Slider);
      
      var Progress = document.createElement('span');
      this.Progress = $(Progress);
      $(Slider).append(Progress);
      
      var Status = document.createElement('div');
      $(Status).addClass('ProgressStatus');
      this.Status = $(Status);
      $(this.Frame).append(Status);
      
      this.SetStatus(this.GetFillerWord());
      this.SetProgress(0);
   }
   
   Gdn_Updater.prototype.SetStatus = function(StatusText) {
      this.Status.html(StatusText);
   }
   
   Gdn_Updater.prototype.SetProgress = function(Percent) {
      this.Progress.html(Percent+'%');
   }

   Gdn_Updater.prototype.PreloadQueue = function(QueueJSON) {
      for (prop in QueueJSON) {
         
      }
   }
   
   Gdn_Updater.prototype.Start = function() {
      if (this.Queue.length) return;
      
      var ResultsBox = $('div.CatchupBlock div.CatchupResults');
      ResultsBox.html('Preparing to run catchup queue...');
      
      $.ajax({
         url: gdn.url('plugin/statistics/startcatchup'),
         dataType: 'json',
         success: jQuery.proxy(this.PreloadQueue, this)
      });
   }
   
   Gdn_Updater.prototype.GetFillerWord = function() {
      if (!this.Words.length) {
         this.Words = [
            'Reticulating Splines',
            'Compositing Latice Structure',
            'Calibrating Defense Matrix',
            'Scanning Plasma Manifolds',
            'Inverting Warp Fields',
            'Dampening Inertial Waveforms',
            'Measuring Graviton Flux',
            'Resolving Package Dependancies',
            'Sorting Recyclable Materials',
            'Probing Spacetime'
         ];
      }
      
      return this.Words[Math.floor(Math.random()*this.Words.length)] + '...';
   }
   
   Gdn_Updater.prototype.Catchup = function(Element) {
      if (Element != '' && Element != undefined) {
         this.Queue.push(Element);
      }
      
      // If we got nothing in queue, gtfo
      if (!this.Queue.length) return;
      
      // If we are not currently busy, get
      if (!this.Active) {
         
         var NextQueueItem = this.Queue.shift();
         this.Active = NextQueueItem;
         
         $.ajax({
            url: gdn.url('plugin/statistics/execcatchup/'+this.Active),
            dataType: 'json',
            success: jQuery.proxy(this.DoneCatchup, this)
         });
         
         this.Monitor();
      }
   }
   
   Gdn_Updater.prototype.Monitor = function(data, status, xhr) {
      
      if (data == undefined) {
         if (this.Active == false) return;
         this.MonitorQuery(this.Active);
      } else {
         if (data.Progress) {
            var Element = data.Progress.Item;
            
            var ResultBox = $('div.CatchupBlock div.CatchupResults');
            ResultBox.find('div#CatchupResult_'+Element+' span.CatchupValue').html(data.Progress.Completion+'%');
            if (data.Progress.Completion >= 100) return;
            if (data.Progress.Item != this.Active) return;
         }
         
         
         var Exec = jQuery.proxy(function(){ this.Monitor(); }, this);
         setTimeout(Exec, 4000);
      }
   }
   
   Gdn_Updater.prototype.MonitorQuery = function(Element) {
      $.ajax({
         url: gdn.url('plugin/statistics/monitor/'+Element),
         dataType: 'json',
         success: jQuery.proxy(this.Monitor, this)
      });
   }
   
   Gdn_Updater.prototype.DoneCatchup = function(data, status, xhr) {
      // Final lookup to get last tick
      this.Monitor();
      
      this.Active = false;
      this.Catchup();
   }
}

var Updater;
jQuery(document).ready(function(){
   Updater = new Gdn_Updater();
   Updater.Prepare();
});