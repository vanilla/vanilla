
function Gdn_Updater() {
   this.Words = [];
   this.TaskQueue = false;
   this.Active = false;
   this.Libraries = ['queue'];

   Gdn_Updater.prototype.Ready = function() {
   
      this.Menu = $('div#Panel div#UpdateModule');
      this.Frame = $('div.UpdateProgress');
      if (this.Frame.length) {
         this.Action = this.Frame.html();
         this.Frame.html('');
      }
      
      // Watch for library readiness
      $(document).bind('libraryloaded',jQuery.proxy(function(Event, Library){
         var Available = gdn.available(this.Libraries);
         if (Available)
            this.Prepare();
      },this));
      
      // Require the queue library
      gdn.requires(this.Libraries);
   }
      
   Gdn_Updater.prototype.Prepare = function() {
      if (this.Prepared == true) return;
      this.Prepared = true;
      
      this.TaskQueue = new Gdn_Queue();
      
      try {
         if (!this.Action.length) return;
         var Tasks = jQuery.parseJSON(this.Action);
         
         this.BuildProgressBar();
         this.PreloadQueue(Tasks);
         this.Perform();
      } catch(err) {
         console.log(err);
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
      
      this.SetStatus(this.GetFillerWord(), true);
      this.SetProgressMode('progress');
      this.SetProgress(0);
   }
   
   Gdn_Updater.prototype.SetMenu = function(MenuHTML) {
      var Menu = document.createElement('div');
      $(Menu).html(MenuHTML);
      this.Menu.html($(Menu).find('div#UpdateModule').html());
   }
   
   Gdn_Updater.prototype.SetStatus = function(StatusText, Decay) {
      if (this.StatusMessage == StatusText) return;
      if (StatusText == false) return;
      this.StatusMessage = StatusText;
      
      this.Status.stop();
      this.Status.css({'opacity':1,'display':'block'});
      this.Status.html(StatusText);
      
      if (Decay) {
         var Speed = ((Decay == 'slow') ? 4000 : 2000);
         clearTimeout(this.DecayTimer);
         this.DecayTimer = setTimeout(jQuery.proxy(function(){
            this.Status.fadeOut(500);
         },this),Speed);
      }
   }
   
   Gdn_Updater.prototype.SetProgress = function(Percent) {
   
      // allow this function to be called blank
      if (Percent == undefined)
         Percent = 0;
            
      this.ProgressPercent = Percent;      
      this.Progress.html(Percent+'%');
      this.Slider.css('width',Percent+'%');
   }
   
   Gdn_Updater.prototype.GetProgress = function() {
      return this.ProgressPercent;
   }

   Gdn_Updater.prototype.PreloadQueue = function(QueueJSON) {
      for (prop in QueueJSON)
         this.Queue(prop,QueueJSON[prop]);
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
   
   Gdn_Updater.prototype.SetProgressMode = function(ProgressMode) {
      if (this.ProgressMode == ProgressMode) return;
      
      switch (ProgressMode) {
         case 'spin':
            this.ProgressMode = ProgressMode;
            this.Slider.removeClass('ProgressSlider').addClass('ProgressSpinner').css('width','100%');
         break;
         
         case 'progress':
            this.ProgressMode = ProgressMode;
            this.Slider.removeClass('ProgressSpinner').addClass('ProgressSlider');
            this.SetProgress(0);
         break;
      }
   }
   
   Gdn_Updater.prototype.Queue = function(TaskURL, TaskName) {
      this.TaskQueue.Add({
         "Task":TaskURL,
         "Name":TaskName
      });
   }
   
   Gdn_Updater.prototype.Perform = function() {
      
      // If we've got nothing in the queue, gtfo
      if (!this.TaskQueue.Length()) return true;
      
      // If we are not currently busy, start a task
      if (!this.Active) {
         
         this.Active = this.TaskQueue.Get();
         this.Active.URL = this.Active.Task+'.json';
         this.ProgressPercent = 0;
         $.ajax({
            url: gdn.url(gdn.combinePaths(this.Active.URL,'perform')),
            dataType: 'json',
            success: jQuery.proxy(this.TaskComplete, this)
         });
         
         this.Monitor();
      }
      return false;
   }
   
   Gdn_Updater.prototype.Monitor = function(data, status, xhr) {
      
      if (data == undefined) {
         if (this.Active == false) return;
         this.MonitorQuery();
      } else {
      
         var CheckAgain = true;
         var Fade = true;
         
         var Completion = parseInt(data.Completion);
         if (Completion >= 0 && Completion <= 100) {
         
            if (Completion == 100) {
               Fade = 'slow';
               CheckAgain = false;
            }
            
            if (data.Menu)
               this.SetMenu(data.Menu);
            
            // Defined progress
            this.SetProgressMode('progress');
            
            // Don't update for old responses returned out of order. (should never happen, but defend anyway!)
            if (Completion > this.GetProgress()) {
               this.SetProgress(Completion);
            }
            
         } else if (Completion == -1) {
            
            // Indefinite task length
            this.SetProgressMode('spin');
            Fade = false;
            
         } else if (Completion == -2) {
         
            // Fatal error occured.
            CheckAgain = false;
            this.SetProgress(0);
            //this.SetProgressStatus('bad');
            Fade = false;
            
         }
         
         if (data.Message != undefined) {
            this.SetStatus(data.Message, Fade);
         }
         
         if (CheckAgain) {
            var Exec = jQuery.proxy(function(){ this.Monitor(); }, this);
            setTimeout(Exec, 1000);
         }

      }
   }
   
   Gdn_Updater.prototype.MonitorQuery = function() {
      if (this.Active == false) return;
      $.ajax({
         url: gdn.url(gdn.combinePaths(this.Active.URL,'check')),
         dataType: 'json',
         success: jQuery.proxy(this.Monitor, this)
      });
   }
   
   Gdn_Updater.prototype.TaskComplete = function(data, status, xhr) {
      // Final lookup to get last tick
      this.Monitor();
      
      this.Active = false;
      var Finished = this.Perform();
      if (Finished)
         this.Next();

   }
   
   Gdn_Updater.prototype.Next = function() {
      var Cont = document.createElement('div');
      $(Cont).addClass('ProgressContinue');
      
      var ContClick = document.createElement('a');
      ContClick.href = window.location;
      $(ContClick).html('Next Step');
      
      $(Cont).append(ContClick);
      this.Frame.append(Cont);
   }
   
   Gdn_Updater.prototype.Failed = function() {
   
   }
}

var Updater;
jQuery(document).ready(function(){
   Updater = new Gdn_Updater();
   Updater.Ready();
});