<?php
/**
 * @author Dan Redman <dredman@higherlogic.com>
 * @copyright 2009-2021 Higher Logic LLC.
 * @license GPL-2.0-only
 */

namespace Vanilla\Dashboard\Events;

/**
 * Defines events related to defining statuses specific to the Ideation plugin.
 * These status definitions are to be subsumed by discussion statuses specific to ideation,
 * but until such time, these events are to be fired as ideation-specific statuses are defined and managed,
 * to enable synchronization between ideation-specific statuses and discussion statuses for ideation.
 */
class IdeaStatusDefinitionEvent extends RecordStatusDefinitionEvent {

}
