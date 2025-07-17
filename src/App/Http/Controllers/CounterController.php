<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Models\Counter;

class CounterController extends Controller
{
    /**
     * Retrieve the total number of users and files.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCounters()
    {
        $counters = Counter::select('total_users', 'total_files')->first();

        $this->incrementCounters();

        return response()->json([                
            'total_users' => $counters->total_users,                
            'total_files' => $counters->total_files,       
        ]);
    }

    /**
     * Increment the total number of users and files by a random amount within defined intervals.
     */
    public function incrementCounters()
    {
        $intervals = [        
            ['duration' => 10000, 'userIncrement' => 1, 'fileIncrement' => 143],
            ['duration' => 40000, 'userIncrement' => 3, 'fileIncrement' => 213],
            ['duration' => 60000, 'userIncrement' => 12, 'fileIncrement' => 542],
            ['duration' => 80000, 'userIncrement' => 8, 'fileIncrement' => 712],
            ['duration' => 180000, 'userIncrement' => 0, 'fileIncrement' => 1032],
            ['duration' => 210000, 'userIncrement' => 0, 'fileIncrement' => 0],
            ['duration' => 300000, 'userIncrement' => 4, 'fileIncrement' => 1545],
        ];

        $interval = $intervals[array_rand($intervals)];

        try {
            DB::beginTransaction();

            $counters = DB::table('counters')->select('total_users', 'total_files')->first();
            $userCount = $counters->total_users + $interval['userIncrement'];
            $fileCount = $counters->total_files + $interval['fileIncrement'];

            DB::table('counters')
                ->where('total_users', '<>', $userCount)
                ->orWhere('total_files', '<>', $fileCount)
                ->update([
                    'total_users' => $userCount,
                    'total_files' => $fileCount,
                ]);

            DB::commit();

            return response()->json([            
                'total_users' => $userCount,            
                'total_files' => $fileCount,        
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([            
                'error' => 'Error updating counters',        ], 500);
        }
    }
}