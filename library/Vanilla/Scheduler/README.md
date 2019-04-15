# Vanilla Scheduler

Vanilla Scheduler adds a Message Queue functionality.

In vanilla/vanilla context, a Message Queue allows postponing the execution of a job after the response is processed and sent to the user or even to delegates it to a third-party player for processing.

The main goal of Vanilla Scheduler is to define the different interfaces of operation to accomplish such a task and provided a basic implementation of those interfaces to have an out-of-the-box functionality available.

Vanilla Scheduler is built as a three-layer component:
- Scheduler: The Scheduler is event-based and takes jobs during normal request processing, and dispatch them at the end of the request.
- Driver: The Driver is the component that knows how to handle a Job.
- Job: The job is the actual work that needs to be done. Depending on the kind of job, this could mean the code to run (let's think in a Local Driver implementation) or the information needed to run the job on a remote third-party queue system.

## Default implementations

### DummyScheduler - The default Scheduler implementation
DummyScheduler is actually a `first-in-first-out after-response event-fired scheduler`.
Which means  the following:
- Jobs are scheduled during normal request processing.
- At the end of the request, an event is fired instructing the Scheduler to dispatch the Jobs.
- Jobs are dispatched one by one in the order they were added to the Scheduler.
- After all the jobs are dispatched, an additional event is fired by the DummyScheduler indicating that Job dispatching is over.

Beware that DummyScheduler is the default Scheduler implementation, other implementations might change this behaviour.
You shouldn't rely on this implementation or in any job co-dependency.

### LocalDriver - The default Driver implementation
A driver that accepts jobs and process them locally on the current environment.

## Vanilla Scheduler idiosyncrasy

There is a job to run, the driver knows how to handle it and the scheduler know when to run it.

The workflow is as follows:
+ You add a job to the scheduler.
+ The scheduler knows which driver is going to handle your job and it sends the job to the driver.
+ The driver receives your job and issues a tracking slip to the scheduler.
+ The scheduler issues a tracking slip to you.
+ You can use the tracking slip to know things about the job like the id, the status or the extended status. Or, you could ignore the tracking slip, and bind to the scheduler's dispatched event to get a notification after jobs are dispatched. Or, you could totally ignore the tracking slip if you want something like a fire-and-forget execution.

Let's see some code... by default Vanilla Scheduler would configure the container to add the DummyScheduler and LocalDriver and the LocalDriver know how to handle jobs that implements `\Vanilla\Scheduler\Job\LocalJobInterface`.

```php
/*
 * We ask the container for the Scheduler.
 * We use `\Vanilla\Scheduler\SchedulerInterface::class`. It allows us to get whatever Scheduler is set on the container
 */

 $scheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);  /* @var $scheduler \Vanilla\Scheduler\SchedulerInterface */

/*
 * We add a Job to the Scheduler.
 * We use `\MyNameSpace\MyJob::class` as jobType and an array with the message.
 *
 * Be aware, we use `\MyNameSpace\MyJob::class` as jobType because of the Scheduler
 * allows us to inject whatever implementation we want for that class into the containter.
 * If there is not custom implementation, then the actual class would be instanciated.
 * Also, because the container is intanciating the Job, the Job must implement a Type-Hinted constructor, a default constructor or a Factory.
 *
 * This is one of the most important points into Scheduler design as it allows a great level of flexibility in terms of which actual job implementation will run.
 */
 $trackingSlip = $dummyScheduler->addJob(\MyNameSpace\MyJob::class, [ /* message */ ]);
```

Ok, so one question should be raised very fast. How the Scheduler knows which Driver should handle the job. Interface is the name of the game.
As part of the bootstrap process some drivers would get added to the Scheduler. When a Driver is added to the Scheduler it would indicate which interfaces it handles.
For example:
```php
$scheduler = $container->get(\Vanilla\Scheduler\SchedulerInterface::class);  /* @var $scheduler \Vanilla\Scheduler\SchedulerInterface */
$scheduler->addDriver(\Vanilla\Scheduler\Driver\LocalDriver::class);
```
So, when we do `$dummyScheduler->addJob(\MyNameSpace\MyJob::class, [ /* message */ ]);` what really happens is:
+ The Scheduler ask the container for an instance of \MyNameSpace\MyJob. That could be an actual \MyNameSpace\MyJob or something defined as it into the container.
+ The Scheduler loop the jobType that it know (in the registered order) looking for that interface in the instance. When there is a match that is the driver that is going to handle the job.
+ If the Job implements more than one Driver interface, then the order of Driver registration into the Scheduler takes precedence over which Driver is going to handle the job.
+ The job would be handled by the Driver. That means the Driver is going to do whatever is needed to do to assure that the job is received by the queue (local or remote)
+ For consistency with the Scheduler operation, a receive Job should not be executed until the order to execute is issued. This is important for Remote system that should handle the initialization of the Job as a two step process.

Flexibility is at hand, as we could:
+ Run a Job as is it.
+ Extended a Job class, redefine some aspect of it, inject the new class into the container and get the new implementation when the original job is called.
+ Create a new Job class, handled by the same driver or any other driver, inject the new class in into the container and get the new implementation when the original job is called.

### Roadmap
+ Some nice-to-have features for the future:
    + In case a Job implements more than one Driver interface, the Job could hint the Scheduler of the order of preference to pick a Driver.
    + In case a Job implements more than one Driver interface, the Job could hint the Scheduler to use one Driver as primary and others as fallback.
