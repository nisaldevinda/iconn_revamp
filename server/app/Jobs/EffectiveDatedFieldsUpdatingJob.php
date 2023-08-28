<?php

namespace App\Jobs;

use App\Library\Model;
use Illuminate\Support\Facades\DB;

class EffectiveDatedFieldsUpdatingJob extends AppJob
{
    protected $parentModel;
    protected $parentRecordId;
    protected $childModel;
    protected $childRecordId;
    protected $effectiveDate;
    protected $updatedData;
    protected $tenantId;

    /**
     * Create a new EffectiveDatedFieldsUpdatingJob instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->tenantId = $data['tenantId'] ?? null;
        $this->parentModel = $data['parentModel'] ?? null;
        $this->parentRecordId = $data['parentRecordId'] ?? null;
        $this->childModel = $data['childModel'] ?? null;
        $this->childRecordId = $data['childRecordId'] ?? null;
        $this->effectiveDate = $data['effectiveDate'] ?? null;
        $this->updatedData = $data['updatedData'] ?? null;
    }

    /**
     * Execute the EffectiveDatedFieldsUpdatingJob.
     *
     * @return void
     */
    public function handle()
    {
        $this->setConnection($this->tenantId);

        $queueJobId = $this->job->getJobId();
        $jobRecord = DB::table('delayed_queue_job')
            ->where('queueJobId', $queueJobId)
            ->update(['isCancelled' => false]);

        $childRecord = DB::table($this->childModel)
            ->where('id', $this->childRecordId)
            ->first();

        if (!empty($jobRecord) && $this->effectiveDate == $childRecord('effectiveDate')) {
            DB::table($this->parentModel)
                ->where('id', $this->parentRecordId)
                ->update($this->updatedData);
        }

        DB::table('delayed_queue_job')
            ->where('queueJobId', $queueJobId)
            ->delete();
    }
}
