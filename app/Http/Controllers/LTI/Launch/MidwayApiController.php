<?php

namespace App\Http\Controllers\LTI\Launch;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use App\Models\CourseContext;
use App\Models\LtiFakeUser;
use App\Models\Tool;

class MidwayApiController extends Controller
{
    /**
     * Returns configuration information for the shim.
     *
     * @return \Illuminate\Http\Response
     */
    public function getLtiFakeUsers(
        Request $request,
        CourseContext $courseContext,
        Tool $tool
    ) {
        // make sure the token we got is allowed to access the given course
        // context and tool
        $user = $request->user();
        if (!$user->tokenCan(
            $user->getLookupAbility($courseContext->id, $tool->id))) {
            return response()->json(['error' => 'Not authorized.'], 403);
        }

        $queryParams = $request->validate([
            'isToolSide' => 'required|in:true,false',
            'perPage' => 'integer|between:1,100',
            'page' => 'integer|min:1',
            'sortField' => 'nullable|in:name,student_number,lti_real_user.name',
            'sortType' => 'nullable|in:asc,desc',
            'search' => 'nullable|string'
        ]);
        // set default values
        $queryParams['isToolSide'] = filter_var($queryParams['isToolSide'],
                                                FILTER_VALIDATE_BOOLEAN);
        $queryParams['perPage'] = $queryParams['perPage'] ?? '10';
        $queryParams['sortType'] = $queryParams['sortType'] ?? 'asc';
        $queryParams['sortField'] = $queryParams['sortField'] ?? 'name';


        // return the users for this course context/tool pair
        $users = LtiFakeUser::where('course_context_id', $courseContext->id)
                            ->where('tool_id', $tool->id)
                            ->orderBy($queryParams['sortField'],
                                      $queryParams['sortType']);
        if ($queryParams['search']) {
            // escape special characters used in sql LIKE patterns
            $searchTerm = Str::of($queryParams['search'])
                            ->replace('%', '\\%')
                            ->replace('_', '\\_')
                            ->lower();
            // while postgres has a special case insensitive version of LIKE,
            // other databases need to use the trick of lower casing everything
            // to get case insensitive comparisons
            $users = $users->where(function($query) use($searchTerm) {
                $query->where(
                        DB::raw('LOWER(name)'),
                        'LIKE',
                        '%'.$searchTerm.'%'
                    )
                    ->orWhere(
                        DB::raw('LOWER(student_number)'),
                        'LIKE',
                        '%'.$searchTerm.'%'
                    );
            });
        }
        // execute the search with pagination
        $users = $users->paginate($queryParams['perPage']);

        // hide some fields we send out
        $users->makeHidden(['login_hint', 'sub', 'lti_real_user_id', 'tool_id',
            'course_context_id', 'created_at', 'updated_at']);
        foreach ($users as $user) {
            $user->lti_real_user->makeHidden(['login_hint', 'email', 'sub',
                'non_lti_id', 'platform_id', 'created_at', 'updated_at']);
        }

        return $users;
    }
}
