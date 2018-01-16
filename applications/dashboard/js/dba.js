jQuery(document).ready(function($) {
    if (!gdn.definition('Started', false))
        return;

    var jobs = {};

    // Gather up all of the jobs.
    $('.DBA-Job').each(function() {
        if ($('input:checked', this).length == 0)
            return;

        jobs[$(this).attr('id')] = {
            rel: $(this).attr('rel'),
            count: 0,
            complete: false
        };
    });

    var tickJob = function() {
        // Find a job in the list.
        for (var id in jobs) {
            var $row = $('#' + id);
            var job = jobs[id];

            if (job.complete)
                continue;

            var url = gdn.url(job.rel);
            if (job.args) {
                url += '&' + $.param(job.args);
            }

            $('.TinyProgress, .DBA-percentage', $row).show();

            $.ajax({
                url: url,
                type: 'POST',
                data: {Postback: true, TransientKey: gdn.definition('TransientKey', '')},
                success: function(data) {
                    var result = data.Result;

                    if (result == undefined) {
                        gdn.informMessage("Did not get a result for " + id);
                        jobs[id].complete = true;
                        $row.addClass('Complete Error');
                    }

                    if (result.Count != undefined) {
                        jobs[id].count += result.Count;

                        var count = jobs[id].count;
                        if (result.Percent) {
                            count = count + ' • ' + result.Percent;
                        }
                        $('.Count', $row).text(count);
                    } else if (result.Percent) {
                        $('.Count', $row).text(result.Percent);
                    }

                    if (result.Complete) {
                        jobs[id].complete = true;
                        $('.Count', $row).text('100%');
                        $('.TinyProgress', $row).remove();
                        $row.find('.js-onComplete').addClass('Complete');
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
