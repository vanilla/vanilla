function Gdn_Queue() {

   this.Queue = [];
   this.ActiveItem = false;
   
   Gdn_Queue.prototype.Add = function(QueueObject) {
      this.Queue.push(QueueObject);
   }
   
   Gdn_Queue.prototype.Length = function() {
      return this.Queue.length;
   }
   
   Gdn_Queue.prototype.Get = function() {
      if (!this.Length()) return false;
      return this.Queue.shift();
   }

}

gdn.loaded('queue');