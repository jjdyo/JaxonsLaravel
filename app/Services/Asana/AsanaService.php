<?php

namespace App\Services\Asana;

use Illuminate\Support\Facades\Http;

class AsanaService
{
    private string $token;
    private string $workspaceGid;

    public function __construct(?string $token = null, ?string $workspaceGid = null)
    {
        // Prefer explicit constructor args; otherwise pull from config. Avoid env() at runtime for config:cache safety.
        $cfgToken = config('services.asana.token');
        $cfgWorkspace = config('services.asana.workspace_gid');

        $this->token = is_string($token) && $token !== ''
            ? $token
            : (is_string($cfgToken) ? $cfgToken : '');

        $this->workspaceGid = is_string($workspaceGid) && $workspaceGid !== ''
            ? $workspaceGid
            : (is_string($cfgWorkspace) ? $cfgWorkspace : '');
    }

    /**
    * Query Asana typeahead for projects.
    * @param string|null $query
    * @param int $count
    * @return array<int, array{gid:string,name:string,resource_type?:string}>
    */
    public function typeaheadProjects(?string $query, int $count = 5): array
    {
        $query = is_string($query) ? trim($query) : '';
        $count = max(1, min(100, $count));

        $url = sprintf('https://app.asana.com/api/1.0/workspaces/%s/typeahead', urlencode($this->workspaceGid));

        $resp = Http::timeout(6)
            ->withToken($this->token)
            ->acceptJson()
            ->get($url, [
                'resource_type' => 'project',
                'query' => $query,
                'count' => $count,
            ]);

        if (!$resp->successful()) {
            return [];
        }

        $data = $resp->json('data');
        if (!is_array($data)) {
            return [];
        }

        $out = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            $gid = isset($item['gid']) && is_string($item['gid']) ? $item['gid'] : null;
            $name = isset($item['name']) && is_string($item['name']) ? $item['name'] : null;
            if ($gid && $name) {
                $out[] = [
                    'gid' => $gid,
                    'name' => $name,
                    'resource_type' => isset($item['resource_type']) && is_string($item['resource_type']) ? $item['resource_type'] : 'project',
                ];
            }
        }
        return $out;
    }

    /**
    * Get a project details by gid, returns minimal fields including permalink_url and name.
    * @param string $projectGid
    * @return array{gid?:string,name?:string,permalink_url?:string} | null
    */
    public function getProject(string $projectGid): ?array
    {
        $projectGid = trim($projectGid);
        if ($projectGid === '') {
            return null;
        }

        $url = sprintf('https://app.asana.com/api/1.0/projects/%s', urlencode($projectGid));
        $resp = Http::timeout(6)
            ->withToken($this->token)
            ->acceptJson()
            ->get($url, [
                'opt_fields' => 'gid,name,permalink_url',
            ]);

        if (!$resp->successful()) {
            return null;
        }

        $data = $resp->json('data');
        if (!is_array($data)) {
            return null;
        }

        $gid = isset($data['gid']) && is_string($data['gid']) ? $data['gid'] : null;
        $name = isset($data['name']) && is_string($data['name']) ? $data['name'] : null;
        $url = isset($data['permalink_url']) && is_string($data['permalink_url']) ? $data['permalink_url'] : null;

        return array_filter([
            'gid' => $gid,
            'name' => $name,
            'permalink_url' => $url,
        ], fn($v) => $v !== null && $v !== '');
    }
}
