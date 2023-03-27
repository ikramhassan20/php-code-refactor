<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Response;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{
    // move these lines in config/constants.php file
    const DEFAULT_SUPER_ADMIN_ROLE_ID = 1;
    const DEFAULT_ADMIN_ROLE_ID = 2;
    const SUCCESS_CODE = 200;
    const ERROR_CODE = 400;
    const INVALID_REQUEST_CODE = 401;

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository){

        $this->repository = $bookingRepository;
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function index(Request $request) {

        // required to get environment variables using config
        // getting environment variables using env will not work
        // getting admin role ids from config
        $admin_role_id = (!empty(config('app.admin_role_id'))) ? config('app.admin_role_id') : DEFAULT_ADMIN_ROLE_ID; // setting default admin role in case not set by default
        $super_admin_role_id = (!empty(config('app.super_admin_role_id'))) ? config('app.super_admin_role_id') : DEFAULT_SUPER_ADMIN_ROLE_ID; // setting default super admin role id
        $admin_ids = [$admin_role_id, $super_admin_role_id];

        $user_id = $request->get('user_id');
        if(empty($request->__authenticatedUser->user_type) || $user_id == ''){
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. No user type found.']);
        }

        // setting default response
        $response = [];
        if($user_id) {
            $response = $this->repository->getUsersJobs($user_id);
        }
        elseif(in_array($request->__authenticatedUser->user_type, $admin_ids)) {
            $response = $this->repository->getAll($request);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Data found.', 'data' => $response]);
    }

    /**
     * @param $id
     * @return Response mixed
     */
    public function show($id) {

        if($id) {
            $job = $this->repository->with('translatorJobRel.user')->find($id);
            if(empty($job)){
                return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'No data found', 'data' => []]);
            }
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. Id is required.']);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Job data found.', 'data' => $job]);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function store(Request $request) {

        $data = $request->all();
        $data['user'] = $request->__authenticatedUser;

        if($data && $data['user']) {
            $response = $this->repository->store($data['user'], $data);
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. Please try again.']);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Data updated.', 'data' => $response]);
    }

    /**
     * @param $id
     * @param Request $request
     * @return Response mixed
     */
    public function update($id, Request $request) {

        $data = $request->all();
        $cuser = $request->__authenticatedUser;

        if($id && $data && $cuser) {

            try {
                $response = $this->repository->updateJob($id, array_except($data, ['_token', 'submit']), $cuser);
            }
            catch (\Exception $exception){
                return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => $exception->getMessage()]);
            }
        }
        else {
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. Please try again.']);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Updated job successfully.', 'data' => $response]);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function immediateJobEmail(Request $request) {

        $data = $request->all();
        if($data) {
            $response = $this->repository->storeJobEmail($data);
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. Please try again.']);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Store job email successfully.', 'data' => $response]);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function getHistory(Request $request) {

        $user_id = $request->get('user_id');
        if($user_id && $request) {

            try {
                $response = $this->repository->getUsersJobsHistory($user_id, $request);
            }
            catch (\Exception $exception) {
                return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => $exception->getMessage()]);
            }
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. Please try again.']);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Users jobs history data found.', 'data' => $response]);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function acceptJob(Request $request) {

        $data = $request->all();
        $data['user'] = $request->__authenticatedUser;

        if($data && $data['user']) {

            try {
                $response = $this->repository->acceptJob($data, $data['user']);
            }
            catch (\Exception $exception){
                return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => $exception->getMessage()]);
            }
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. Please try again.']);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Accepted job.', 'data' => $response]);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function acceptJobWithId(Request $request) {

        $data['job_id'] = $request->only('job_id');
        $data['user'] = $request->__authenticatedUser;

        if($data['job_id']) {
            try {
                $response = $this->repository->acceptJobWithId($data['job_id'], $data['user']);
            } catch (\Exception $exception) {
                return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => $exception->getMessage()]);
            }
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Job id is required.']);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Accept job with id.', 'data' => $response]);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function cancelJob(Request $request) {

        $data = $request->all();
        $data['user'] = $request->__authenticatedUser;

        if($data['user']) {
            try {
                $response = $this->repository->cancelJobAjax($data, $data['user']);
                return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Cancel job ajax.', 'data' => $response]);
            }
            catch (\Exception $exception){
                return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => $exception->getMessage()]);
            }
        }
        else {
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. No authenticated user found.']);
        }
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function endJob(Request $request) {

        $data = $request->all();

        try {
            $response = $this->repository->endJob($data);
        }
        catch (\Exception $exception) {
            return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => $exception->getMessage()]);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Job ended.', 'data' => $response]);

    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function customerNotCall(Request $request) {

        $data = $request->all();
        try {
            $response = $this->repository->customerNotCall($data);
        }
        catch (\Exception $exception) {
            return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => $exception->getMessage()]);
        }

        return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Customer data found.', 'data' => $response]);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function getPotentialJobs(Request $request) {

        $user = $request->__authenticatedUser;

        if($user) {
            $response = $this->repository->getPotentialJobs($user);
            return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Jobs data found.', 'data' => $response]);
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. No authenticated user found.']);
        }
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function distanceFeed(Request $request) {

        // getting request object
        $data = $request->only(['jobid', 'distance', 'time', 'session_time', 'manually_handled', 'by_admin', 'admincomment', 'flagged']);

        // prepare and parse required data
        $job_id = (!empty($data['jobid'])) ? $data['jobid'] : false;
        $distance = (!empty($data['distance'])) ? $data['distance'] : '';
        $time = (!empty($data['time'])) ? $data['time'] : '';
        $session = (!empty($data['session_time'])) ? $data['session_time'] : '';
        $manually_handled = ($data['manually_handled']) ? 'yes' : 'no';
        $by_admin = ($data['by_admin']) ? 'yes' : 'no';
        $admin_comments = (!empty($data['admincomment'])) ? $data['admincomment'] : '';

        if ($data['flagged']) {
            if($admin_comments == '') return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Admin comment is required.']);
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }

        if($job_id) {

            $affected_rows = false;
            if ($time || $distance) {
                $affected_rows = Distance::where('job_id', '=', $job_id)->update(['distance' => $distance, 'time' => $time]);
            }

            if ($admin_comments || $session || $flagged || $manually_handled || $by_admin) {

                $affected_rows = Job::where('id', '=', $job_id)
                    ->update([
                        'admin_comments' => $admin_comments,
                        'flagged' => $flagged,
                        'session_time' => $session,
                        'manually_handled' => $manually_handled,
                        'by_admin' => $by_admin
                    ]);
            }

            if($affected_rows) {
                return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Record updated.']);
            }
            else{
                return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Unable to update.']);
            }
        }
        else{
            return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Job id is required.']);
        }
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function reopen(Request $request) {

        $data = $request->all();
        $response = $this->repository->reopen($data);

        return response($response);
    }

    /**
     * @param Request $request
     * @return Response mixed
     */
    public function resendNotifications(Request $request) {

        // it's better to get only the required request param in case of only few known fields
        // rather than getting entire request object that is not in used
        $data['job_id'] = $request->only('jobid');

        // then: it's better to handle request by type i.e. GET or POST
        // I am using request->only for now as because not sure about the route type
        // $data['job_id'] = $request->get('jobid');
        // $data['job_id'] = $request->post('jobid');

        if($data['job_id']) {

            $job = $this->repository->find($data['jobid']);
            $job_data = $this->repository->jobToData($job);

            try {
                $this->repository->sendNotificationTranslator($job, $job_data, '*');
                return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Push sent successfully.']);
            } catch (\Exception $e) {
                return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => 'Unable to sent Push due to error: '. $e->getMessage()]);
            }
        }
        return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Job id is required.']);
    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request) {

        // it's better to get only the required request param in case of only few known fields
        // rather than getting entire request object that is not in used
        $data['job_id'] = $request->only('jobid');

        // then: it's better to handle request by type i.e. GET or POST
        // I am using request->only for now as because not sure about the route type
        // $data['job_id'] = $request->get('jobid');
        // $data['job_id'] = $request->post('jobid');

        if($data['job_id']) {
            $job = $this->repository->find($data['job_id']);
            $job_data = $this->repository->jobToData($job);

            try {
                $this->repository->sendSMSNotificationToTranslator($job);
                return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'SMS sent successfully.']);
            } catch (\Exception $e) {
                return response(['status' => 'error', 'status_code' => ERROR_CODE, 'message' => 'Unable to sent SMS due to error: '. $e->getMessage()]);
            }
        }
        return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Job id is required.']);
    }

}
