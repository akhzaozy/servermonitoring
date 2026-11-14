<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MonitoredSite;
use App\Services\WebHealthService;
use App\Support\SitePayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteApiController extends Controller
{
    public function __construct(
        protected WebHealthService $webHealth
    ) {}

    /**
     * List all monitored sites with current stats.
     */
    public function index(): JsonResponse
    {
        $sites = SitePayload::all($this->webHealth);

        return response()->json($sites);
    }

    /**
     * Create new monitored site.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'url' => 'required|url|max:255',
            'stack_type' => 'required|in:nextjs,laravel,native_php,static,api,other',
            'port' => 'nullable|integer|between:1,65535',
            'expected_keyword' => 'nullable|string|max:100',
        ]);

        $site = MonitoredSite::create([
            'name' => $validated['name'],
            'url' => $validated['url'],
            'stack_type' => $validated['stack_type'],
            'port' => $validated['port'] ?? null,
            'expected_keyword' => $validated['expected_keyword'] ?? null,
            'is_active' => true,
        ]);

        // Immediately perform first check
        $this->webHealth->checkSite($site);

        return response()->json([
            'message' => 'Website berhasil ditambahkan ke monitoring!',
            'site' => $site->fresh(),
        ], 201);
    }

    /**
     * Update an existing site.
     */
    public function update(Request $request, MonitoredSite $site): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:100',
            'url' => 'sometimes|required|url|max:255',
            'stack_type' => 'sometimes|required|in:nextjs,laravel,native_php,static,api,other',
            'port' => 'nullable|integer|between:1,65535',
            'is_active' => 'sometimes|boolean',
            'expected_keyword' => 'nullable|string|max:100',
        ]);

        $site->update($validated);

        return response()->json([
            'message' => 'Pengaturan website berhasil diperbarui!',
            'site' => $site->fresh(),
        ]);
    }

    /**
     * Delete a monitored site.
     */
    public function destroy(MonitoredSite $site): JsonResponse
    {
        $site->delete();

        return response()->json(['message' => 'Website berhasil dihapus dari monitoring.']);
    }

    /**
     * Trigger immediate check for single site.
     */
    public function check(MonitoredSite $site): JsonResponse
    {
        $res = $this->webHealth->checkSite($site);
        $stats = $this->webHealth->getSiteUptimeStats($site->fresh());

        return response()->json([
            'check' => $res,
            'uptime_percentage' => $stats['uptime_percentage'],
            'history_bars' => $stats['history_bars'],
        ]);
    }

    /**
     * Trigger immediate check for ALL sites.
     */
    public function checkAll(): JsonResponse
    {
        $this->webHealth->checkAllSites();

        return $this->index();
    }
}
