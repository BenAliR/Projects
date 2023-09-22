<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;

class ActivityService
{
    public function logActivity($logName, $description, $subjectType, $subjectId, $causerType, $causerId)
    {
        $activity = new Activity();
        $activity->log_name = $logName;
        $activity->description = $description;
        $activity->subject_type = $subjectType;
        $activity->subject_id = $subjectId;
        $activity->causer_type = $causerType;
        $activity->causer_id = $causerId;
        $activity->save();
    }

    public function getActivitiesBySubject($subjectType, $subjectId)
    {
        $activities = Activity::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->get();

        return $activities;
    }

    // Add more methods as needed for activity-related operations
}
