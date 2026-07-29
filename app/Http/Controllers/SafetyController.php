<?php

namespace App\Http\Controllers;

use App\Services\Accounts\CurrentContext;
use App\Services\Safety\SafetyAuditor;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SafetyController extends Controller
{
    public function __construct(
        private readonly SafetyAuditor $auditor,
        private readonly CurrentContext $context,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $cruise = $this->context->cruise($user);
        $boat = $this->context->boat($user, $cruise);

        return Inertia::render('security', [
            'securityData' => $this->auditor->audit($boat, $cruise),
            'cruise' => $cruise === null ? null : [
                'slug' => $cruise->slug,
                'title' => $cruise->title,
                'departure_port' => $cruise->departure_port,
                'destination_port' => $cruise->destination_port,
            ],
        ]);
    }
}
