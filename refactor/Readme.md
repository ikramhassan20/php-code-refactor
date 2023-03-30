
## Laravel Code Re-factor

* I have done the required task that is Re-Factoring the provided code. Due to time factor unable to continue on the writing test-cases.
* In Code Re-Factoring, I tried to covered as much in this time span but I think it can be more optimized and re-factored.
* I did not have the Booking Controller / Booking Repository Jobs scope or internal flow overall, but it seems like the structure and implementation flow seems OK.
* However there were some minor improvements might needed in terms of coding standards and best practices and generating error/success responses.
* So I have tried to modified the API responses (success/error/exceptions) that I feel to meet the standard way to make similar responses. Few key names were modified as well that needs to affected where its required.
* Time Spend: 4 Hours

### - Code to refactor - DONE
--------------------------------
1) app/Http/Controllers/BookingController.php
2) app/Repository/BookingRepository.php

### - Code to write tests (optional) - Not Done
-----------------------------------------------
3) App/Helpers/TeHelper.php method willExpireAt
4) App/Repository/UserRepository.php, method createOrUpdate

### Commits

*. Initial Commit contains the original code.
*. Second commit contains the Re-factor code of the following files:
1) app/Http/Controllers/BookingController.php
2) app/Repository/BookingRepository.php

### - Good vs Bad about the code - WHY?
------------------------------------------------------------------------
The code was well written, easy to read, understand with following major good vs bad points:
1) BookingController is using the Repository pattern that is a good thing. However, Repository not implemented the BookingRepositoryInterface. As Repositories shouldn't to create or update data, but should only be used to retrieve data. For Example:
1.1) In the app directory, create a new folder called Interfaces. Then, in the Interfaces, create a new file called BookingRepositoryInterface.php and add the following code to it.
```
<?php

namespace App\Interfaces;

interface BookingRepositoryInterface 
{
    public function getAll();
    public function getUsersJobs($id);
    public function store(array $data);
    public function update($id, array $new_data);
}
```

1.2) Next, in the app folder, create a new folder called Repositories. In this folder, create a new file called BookingRepository.php and add the following code to it.
```
<?php

namespace App\Repositories;

use App\Interfaces\BookingRepositoryInterface;
use App\Models\User;
use App\Models\UserMeta;

class BookingRepository implements BookingRepositoryInterface 
{
    public function getAll() 
    {
        return User::all();
    }

    public function getUsersJobs($user_id) 
    {
        return User::findOrFail($user_id);
    }

    public function store(array $data) 
    {
        return User::create($data);
    }

    public function update($id, array $new_data) 
    {
        return User::whereId($id)->update($new_data);
    }
}
```

1.3) With repository in place, add some example in BookingController.
```
<?php

namespace App\Http\Controllers;

use App\Interfaces\BookingRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BookingController extends Controller 
{
    private BookingRepositoryInterface $bookingRepository;

    public function __construct(BookingRepositoryInterface $bookingRepository) 
    {
        $this->repository = $bookingRepository;
    }

    public function index(Request $request): JsonResponse 
    {
        $response = [];
        $user_id = $request->get('user_id');
        if($user_id) {
            $response = $this->repository->getUsersJobs($user_id);
        }
        elseif(in_array($request->__authenticatedUser->user_type, $admin_ids)) {
            $response = $this->repository->getAll($request);
        }
        return response()->json([
            'data' => $response
        ]);
    }

    public function show(Request $request): JsonResponse 
    {
        return response()->json([
            'data' => $this->repository->with('translatorJobRel.user')->find($id);
        ]);
    }

    public function store(Request $request): JsonResponse 
    {
        $data = $request->all();
        $data['user'] = $request->__authenticatedUser;

        if($data && $data['user']) {
            $response = $this->repository->store($data['user'], $data);
        }
        
        return response()->json([
            'data' => $response
        ]);
    }
}
```
The code injects an `BookingRepositoryInterface` instance via the constructor and uses the relevant object's methods in each controller method.

1.4) Next requires to bind BookingRepository to BookingRepositoryInterface in Service Container via Service Provider. Run:
```
php artisan make:provider RepositoryServiceProvider
```

Open `app/Providers/RepositoryServiceProvider.php` and update the register function to match the following.
```
public function register()
{
    $this->app->bind(BookingRepositoryInterface::class, BookingRepository::class);
}
```

1.5) Add the new Service Provider to the `providers` array in `config/app.php`.
```
'providers' => [
    // ...other declared providers
    App\Providers\RepositoryServiceProvider::class,
];
```

2) It's Not best practices to use constants and strings as hard-coded within the code. It should be either defined globally or through internationalization.
3) No exception handling applied wile interact with the Jobs. Because working with Jobs is an asynchronous behavior and hard to trace where the problem occurs.  
4) In code there is no Response Status, Code and Messages defined with data object. To provide consistency both error and success response should similar structure.

*For Success Response:*
```
   return response(['status' => 'success', 'status_code' => SUCCESS_CODE, 'message' => 'Data found.', 'data' => $response]);
```

*For Error Response:*
```
    return response(['status' => 'error', 'status_code' => INVALID_REQUEST_CODE, 'message' => 'Invalid request. No user type found.']);
```

5) Missing comments about blocks and definitions. Ideally each block of code should describe in few meaningful words. 
6) Naming conventions are not uniformed somewhere and at some places not as per coding standards.

If you have any question please let me know.

Thanks