<?php

namespace App\Http\Controllers;

use App\Models\User;

class UserController extends Controller
{
  public function create()
  {
    return view('master.users.create');
  }

  public function show(User $user)
  {
    $user->load(['department', 'rank', 'position', 'roles']);

    $search = request('search_trip');

    $tripsAsPelaksanaQuery = \App\Models\SppdRequest::with(['budget', 'category', 'destinations.province', 'destinations.regency'])
      ->where('user_id', $user->id)
      ->orderBy('start_date', 'desc');

    $tripsAsFollowerQuery = \App\Models\SppdRequest::with(['user', 'budget', 'category', 'destinations.province', 'destinations.regency'])
      ->whereHas('followers', function ($q) use ($user) {
        $q->where('user_id', $user->id);
      })
      ->orderBy('start_date', 'desc');

    if (! empty($search)) {
      $tripsAsPelaksanaQuery->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('destinations.province', function ($pq) use ($search) {
            $pq->where('name', 'like', "%{$search}%");
          })
          ->orWhereHas('destinations.regency', function ($rq) use ($search) {
            $rq->where('name', 'like', "%{$search}%");
          });
      });

      $tripsAsFollowerQuery->where(function ($q) use ($search) {
        $q->where('purpose', 'like', "%{$search}%")
          ->orWhere('document_number', 'like', "%{$search}%")
          ->orWhereHas('destinations.province', function ($pq) use ($search) {
            $pq->where('name', 'like', "%{$search}%");
          })
          ->orWhereHas('destinations.regency', function ($rq) use ($search) {
            $rq->where('name', 'like', "%{$search}%");
          });
      });
    }

    $tripsAsPelaksana = $tripsAsPelaksanaQuery->paginate(5, ['*'], 'page_pelaksana')->withQueryString();
    $tripsAsFollower = $tripsAsFollowerQuery->paginate(5, ['*'], 'page_pengikut')->withQueryString();

    return view('master.users.show', compact('user', 'tripsAsPelaksana', 'tripsAsFollower'));
  }

  public function edit(User $user)
  {
    return view('master.users.edit', compact('user'));
  }
}
