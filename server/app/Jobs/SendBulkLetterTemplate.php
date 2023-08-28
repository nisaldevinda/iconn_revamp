<?php

namespace App\Jobs;

use App\Library\Model;
use Illuminate\Support\Facades\DB;
use Throwable;
use Illuminate\Support\Facades\Log;
use Exception;
use App\Services\DocumentTemplateService;
use App\Library\FileStore;
use App\Jobs\EmailNotificationJob;
use App\Library\Email;
class SendBulkLetterTemplate extends AppJob
{

    protected $data;
   
    /**
     * Create a new SendBulkLetterTemplate instance.
     *
     * @return void
     */
    public function __construct($data )
    {
        $this->data = $data;
    
    }

    /**
     * Execute the EmployeeActiveStatusHandlingJob.
     *
     * @return void
     */
    public function handle(DocumentTemplateService $documentTemplateService , FileStore $fileStore )
    {
        try {
                
            $fileName = DB::table('documentTemplate')->where('id', $this->data['templateId'])->first('name');
         
            foreach ($this->data['audienceData']['employeeIds'] as $empId) {
               $content= $documentTemplateService->downloadEmployeeDocumentAsPdf( $empId, $this->data['templateId'], null) ;
             
               $fileData =[];
               $base64String = $content['data'];
               //get fileSize from Base64 encoded string
               $fileData['fileSize'] =(int) (strlen(rtrim( $base64String, '=')) * 3 / 4);
               $fileData['content'] = 'data:application/pdf;base64,'.$base64String ;
               $fileData['fileName'] = $fileName->name;
               

               DB::beginTransaction(); 
          
                $file = $fileStore->putBase64EncodedObject(
                    $fileData['fileName'],
                    $fileData['fileSize'],
                    $fileData['content']
                );
      
              $documentManagerFile = [
                'documentName' => $fileData['fileName'],
                'folderId' =>  $this->data['folderId'],
                'fileId' => $file->id,
                'audienceMethod' => $this->data['audienceType'] ?? null ,
                'audienceData' => $this->data['audienceData'] ? json_encode($this->data['audienceData']) : null ,
                'isDelete' => false
              ];
             
              $newDocumentManagerFile = DB::table('documentManagerFile')->insertGetId($documentManagerFile);
             
              $empDocumentFile = [
                'documentManagerFileId' => $newDocumentManagerFile,
                'employeeId' => $empId ?? null
              ];

              $empDocumentManagerFile = DB::table('documentManagerEmployeeFile')->insertGetId($empDocumentFile);
      

                //Email notification will be sent to the employee
                $employee =DB::table('employee')->where('id', $empId)->first(['workEmail','firstName']);
               
                $emp['firstName'] = $employee->firstName;
                $emailBody ='You have received a letter. Please review the letter by clicking the button below.';
                $emp['link'] = config('app.client_url') ."#/ess/my-info-request";
                $buttonText= 'Letter';
                dispatch(new EmailNotificationJob(new Email('emails.documentManagerEmail', array($employee->workEmail), "Letter", array([]), array("link" => $emp['link'], "firstName" => $emp['firstName'] , "emailBody" => $emailBody , "buttonText" => $buttonText))))->onQueue('email-queue');
               
                DB::table('bulkLetterLog')->where('id', '=', $this->data['bulkLetterLogId'])->update([
                    'status' => 'COMPLETED'
                ]);
               
                DB::commit();
             
            }
            
        } catch (Exception $e) {
            DB::rollback();
            $data = ['status' => 'ERROR', 'note' => $e->getMessage()];
            DB::table('bulkLetterLog')->where('id', '=', $this->data['bulkLetterLogId'])->update($data);
        }
       
    }

     /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $e)
    {
        
        $data = ['status' => 'ERROR', 'note' => $e->getMessage()];
        DB::table('bulkLetterLog')->where('id', '=', $this->data['bulkLetterLogId'])->update($data);
    }
}
