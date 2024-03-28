<?php

namespace HS\Http\Controllers\Admin;

use HS\User;
use HS\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PersonalAccessTokenController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'token-name' => ['required'],
        ]);

        // Current user must be an admin or editing their own account
        if(! auth()->user()->isAdmin() && auth()->user()->getKey() != $request->xPerson) {
            return abort(401);
        }

        return User::findOrFail($request->xPerson)
            ->createToken($request->input('token-name', 'Api Token'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        auth()->user()
            ->tokens()
            ->where('id', $id)
            ->delete();

        return back();
    }
}
