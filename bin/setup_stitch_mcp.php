<?php
declare(strict_types=1);

$apiKey = getenv('STITCH_API_KEY') ?: ($argv[1] ?? 'YOUR_STITCH_API_KEY');
$mcpUrl = 'https://stitch.googleapis.com/mcp';

echo "1. Fetching tools list from Stitch MCP API...\n";
$ch = curl_init($mcpUrl);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        "X-Goog-Api-Key: {$apiKey}"
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'jsonrpc' => '2.0',
        'id' => 1,
        'method' => 'tools/list',
        'params' => new stdClass()
    ])
]);
$resp = curl_exec($ch);
curl_close($ch);

$data = json_decode((string)$resp, true);
if (empty($data['result']['tools'])) {
    echo "[FAIL] Could not fetch tools: " . substr((string)$resp, 0, 200) . "\n";
    exit(1);
}

$tools = $data['result']['tools'];
echo "[OK] Retrieved " . count($tools) . " tools from Google Stitch.\n";

// 2. Create /home/astra/.gemini/antigravity/mcp/stitch
$mcpDir = '/home/astra/.gemini/antigravity/mcp/stitch';
@mkdir($mcpDir, 0775, true);

foreach ($tools as $tool) {
    $toolName = $tool['name'];
    $schema = [
        'name' => $toolName,
        'description' => $tool['description'] ?? '',
        'parameters' => $tool['inputSchema'] ?? ['type' => 'object', 'properties' => new stdClass()]
    ];
    $filePath = $mcpDir . '/' . $toolName . '.json';
    file_put_contents($filePath, json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}
echo "[OK] Saved tool schemas to {$mcpDir}\n";

// 3. Update /home/astra/.gemini/config/mcp_config.json
$configFile = '/home/astra/.gemini/config/mcp_config.json';
$config = json_decode((string)file_get_contents($configFile), true) ?: ['mcpServers' => []];

$config['mcpServers']['stitch'] = [
    'serverUrl' => $mcpUrl,
    'headers' => [
        'X-Goog-Api-Key' => $apiKey
    ]
];

file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "[OK] Updated {$configFile} with Google Stitch MCP.\n";

// 4. Also create instructions.md for Stitch MCP in antigravity/mcp/stitch/instructions.md
$instructions = <<<MD
# Google Stitch MCP Server

Stitch allows generating, editing, and retrieving UI designs and design systems.
- API Key: Configured
- Project: projects/2724570344893930216 ("TechRadar DNS Aggregator")
- Screen: projects/2724570344893930216/screens/2565bd7b830841299b6db038359e2eab

Available tools:
- list_projects
- get_project
- list_screens
- get_screen
- generate_screen_from_text
- edit_screens
- create_design_system
MD;
file_put_contents($mcpDir . '/instructions.md', $instructions);
echo "[OK] Created instructions.md in {$mcpDir}\n";
