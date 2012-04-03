jQuery(document).ready(function($) {
   var jobs = {};
   
   // Gather up all of the jobs.
   $('.DBA-Job').each(function() {
      jobs[$(this).attr('id')] = {
         rel: $(this).attr('rel'),
         count: 0,
         complete: false
         };
   });
   
   var tickJob = function() {
      // Find a job in the list.
      for(var id in jobs) {
         var $row = $('#'+id);
         var job = jobs[id];
         
         if (job.complete)
            continue;
         
         var url = job.rel;
         if (job.args) {
            url += '&'+$.param(job.args);
         }
         
         $.ajax({
            url: url,
            type: 'POST',
            success: function(data) {
               var result = data.Result;
               
               if (result == undefined) {
                  gdn.informMessage("Did not get a result for "+id);
                  jobs[id].complete = true;
                  $row.addClass('Complete Error');
               }
               
               if (result.Count) {
                  jobs[id].count += result.Count;
                  $('.Count', $row).show().text(jobs[id].count);
               }
               
               if (result.Complete) {
                  jobs[id].complete = true;
                  
                  $('.TinyProgress', $row).remove();
                  $row.addClass('Complete');
               }
               
               if (result.Args) {
                  // Set the job's new args for the next call.
                  jobs[id].args = result.Args;
               }
               
               tickJob();
            },
            error: function(xhr) {
               jobs[id].complete = true;
               gdn.informError(xhr);
               $row.addClass('Complete Error');
               
               tickJob();
            }
         });
         break;
      }
   };
   
   tickJob();
});