<?php
declare(strict_types=1);

$dir = dirname(__DIR__) . '/public/assets/img/products';
@mkdir($dir, 0775, true);

$products = [
    // CPU
    'cpu-ryzen.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- CPU Substrate -->
      <rect x="45" y="45" width="210" height="210" rx="8" fill="#15803D" stroke="#166534" stroke-width="2"/>
      <!-- Gold corner triangle -->
      <polygon points="48,48 68,48 48,68" fill="#FACC15"/>
      <!-- Heatspreader -->
      <rect x="65" y="65" width="170" height="170" rx="12" fill="#E2E8F0" stroke="#94A3B8" stroke-width="3"/>
      <!-- Inner IHS design -->
      <rect x="75" y="75" width="150" height="150" rx="8" fill="#CBD5E1"/>
      <circle cx="150" cy="150" r="35" fill="#E2E8F0"/>
      <!-- Laser Etching Text -->
      <text x="150" y="115" font-family="system-ui, sans-serif" font-size="16" font-weight="900" fill="#EA580C" text-anchor="middle">AMD RYZEN</text>
      <text x="150" y="135" font-family="system-ui, sans-serif" font-size="14" font-weight="700" fill="#334155" text-anchor="middle">7 7800X3D</text>
      <text x="150" y="155" font-family="system-ui, sans-serif" font-size="10" font-weight="600" fill="#64748B" text-anchor="middle">3D V-CACHE</text>
      <text x="150" y="195" font-family="monospace" font-size="9" fill="#64748B" text-anchor="middle">DIFFUSED IN TAIWAN</text>
      <text x="150" y="210" font-family="monospace" font-size="8" fill="#94A3B8" text-anchor="middle">MADE IN MALAYSIA</text>
    </svg>',

    'cpu-intel.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- CPU Substrate -->
      <rect x="55" y="40" width="190" height="220" rx="8" fill="#0369A1" stroke="#075985" stroke-width="2"/>
      <polygon points="58,43 76,43 58,61" fill="#FACC15"/>
      <!-- Heatspreader -->
      <rect x="70" y="55" width="160" height="190" rx="10" fill="#E2E8F0" stroke="#94A3B8" stroke-width="3"/>
      <rect x="78" y="63" width="144" height="174" rx="6" fill="#CBD5E1"/>
      <!-- Intel Laser Markings -->
      <text x="150" y="110" font-family="system-ui, sans-serif" font-size="20" font-weight="900" fill="#0284C7" text-anchor="middle">intel</text>
      <text x="150" y="135" font-family="system-ui, sans-serif" font-size="15" font-weight="800" fill="#0F172A" text-anchor="middle">CORE i5</text>
      <text x="150" y="155" font-family="monospace" font-size="11" fill="#475569" text-anchor="middle">i5-12400F</text>
      <text x="150" y="175" font-family="monospace" font-size="9" fill="#64748B" text-anchor="middle">SRL4W 2.50GHz</text>
      <text x="150" y="210" font-family="monospace" font-size="8" fill="#94A3B8" text-anchor="middle">MALAY L123456</text>
    </svg>',

    // GPU
    'gpu-rtx4090.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- PCIe Gold Contacts -->
      <rect x="60" y="215" width="160" height="12" fill="#EAB308"/>
      <!-- Shroud -->
      <rect x="30" y="85" width="240" height="130" rx="12" fill="#1E293B" stroke="#0F172A" stroke-width="3"/>
      <!-- Triple Fans -->
      <circle cx="75" cy="150" r="32" fill="#0F172A" stroke="#475569" stroke-width="2"/>
      <circle cx="75" cy="150" r="12" fill="#334155"/>
      <circle cx="150" cy="150" r="32" fill="#0F172A" stroke="#475569" stroke-width="2"/>
      <circle cx="150" cy="150" r="12" fill="#334155"/>
      <circle cx="225" cy="150" r="32" fill="#0F172A" stroke="#475569" stroke-width="2"/>
      <circle cx="225" cy="150" r="12" fill="#334155"/>
      <!-- RGB Accent Strip -->
      <rect x="40" y="95" width="220" height="6" rx="3" fill="#22C55E"/>
      <!-- GeForce Badge -->
      <rect x="100" y="106" width="100" height="16" rx="4" fill="#000"/>
      <text x="150" y="118" font-family="system-ui, sans-serif" font-size="9" font-weight="900" fill="#84CC16" text-anchor="middle">GEFORCE RTX 4090</text>
    </svg>',

    'gpu-rtx4060.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <rect x="70" y="210" width="130" height="10" fill="#EAB308"/>
      <!-- Dual Fan Shroud -->
      <rect x="40" y="90" width="220" height="120" rx="10" fill="#1E293B" stroke="#0F172A" stroke-width="3"/>
      <!-- Fans -->
      <circle cx="95" cy="150" r="36" fill="#0F172A" stroke="#64748B" stroke-width="2"/>
      <circle cx="95" cy="150" r="14" fill="#334155"/>
      <circle cx="195" cy="150" r="36" fill="#0F172A" stroke="#64748B" stroke-width="2"/>
      <circle cx="195" cy="150" r="14" fill="#334155"/>
      <rect x="50" y="100" width="200" height="5" rx="2" fill="#38BDF8"/>
      <text x="150" y="120" font-family="system-ui, sans-serif" font-size="10" font-weight="900" fill="#FFF" text-anchor="middle">GEFORCE RTX 4060</text>
    </svg>',

    // Motherboard
    'mobo-asus.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- PCB -->
      <rect x="40" y="30" width="220" height="240" rx="8" fill="#1E293B" stroke="#0F172A" stroke-width="2"/>
      <!-- VRM Heatsinks -->
      <rect x="50" y="40" width="30" height="70" rx="4" fill="#475569"/>
      <rect x="85" y="40" width="70" height="25" rx="4" fill="#475569"/>
      <!-- CPU Socket AM5 -->
      <rect x="95" y="75" width="60" height="60" rx="4" fill="#CBD5E1" stroke="#94A3B8"/>
      <!-- 4 RAM Slots -->
      <rect x="175" y="50" width="6" height="90" fill="#000" stroke="#F59E0B"/>
      <rect x="185" y="50" width="6" height="90" fill="#000" stroke="#F59E0B"/>
      <rect x="195" y="50" width="6" height="90" fill="#000" stroke="#F59E0B"/>
      <rect x="205" y="50" width="6" height="90" fill="#000" stroke="#F59E0B"/>
      <!-- PCIe x16 Steel Slot -->
      <rect x="55" y="160" width="130" height="12" rx="2" fill="#E2E8F0" stroke="#64748B"/>
      <!-- M.2 Armor Heatsink -->
      <rect x="55" y="185" width="110" height="20" rx="3" fill="#334155"/>
      <!-- Chipset Heatsink -->
      <rect x="170" y="180" width="65" height="60" rx="6" fill="#334155"/>
      <text x="202" y="215" font-family="system-ui, sans-serif" font-size="11" font-weight="900" fill="#F59E0B" text-anchor="middle">TUF</text>
    </svg>',

    // RAM
    'ram-fury.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Gold Pins -->
      <rect x="35" y="185" width="230" height="12" fill="#EAB308"/>
      <!-- Black Heatsink Module -->
      <rect x="30" y="100" width="240" height="85" rx="8" fill="#18181B" stroke="#27272A" stroke-width="2"/>
      <!-- Aggressive cutouts -->
      <polygon points="50,100 80,100 70,120 40,120" fill="#27272A"/>
      <polygon points="220,100 250,100 240,120 210,120" fill="#27272A"/>
      <!-- RGB Diffuser Bar -->
      <rect x="35" y="93" width="230" height="8" rx="3" fill="#A855F7"/>
      <!-- Branding -->
      <text x="150" y="145" font-family="system-ui, sans-serif" font-size="18" font-weight="900" fill="#EF4444" text-anchor="middle">FURY</text>
      <text x="150" y="165" font-family="system-ui, sans-serif" font-size="10" font-weight="700" fill="#A1A1AA" text-anchor="middle">BEAST DDR5 32GB 6000MHz</text>
    </svg>',

    // SSD
    'ssd-samsung.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- M.2 PCB -->
      <rect x="30" y="115" width="240" height="70" rx="6" fill="#1E293B" stroke="#0F172A" stroke-width="2"/>
      <!-- Gold M.2 connector notch -->
      <rect x="25" y="130" width="6" height="40" fill="#EAB308"/>
      <!-- Chips -->
      <rect x="45" y="125" width="40" height="50" rx="4" fill="#0F172A" stroke="#334155"/>
      <rect x="95" y="125" width="55" height="50" rx="4" fill="#0F172A" stroke="#334155"/>
      <rect x="160" y="125" width="55" height="50" rx="4" fill="#0F172A" stroke="#334155"/>
      <!-- Label -->
      <rect x="40" y="120" width="185" height="60" rx="4" fill="#09090B"/>
      <text x="50" y="145" font-family="system-ui, sans-serif" font-size="13" font-weight="900" fill="#FFF">SAMSUNG</text>
      <text x="50" y="165" font-family="system-ui, sans-serif" font-size="14" font-weight="900" fill="#EF4444">990 PRO</text>
      <text x="135" y="165" font-family="monospace" font-size="11" font-weight="700" fill="#94A3B8">NVMe M.2 2TB</text>
    </svg>',

    // Smartphone
    'phone-iphone15.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Phone Body -->
      <rect x="90" y="30" width="120" height="240" rx="26" fill="#0F172A" stroke="#334155" stroke-width="4"/>
      <!-- Screen -->
      <rect x="94" y="34" width="112" height="232" rx="22" fill="#020617"/>
      <!-- Wallpaper Gradient -->
      <rect x="96" y="36" width="108" height="228" rx="20" fill="#1E1B4B"/>
      <circle cx="150" cy="130" r="40" fill="#6366F1" opacity="0.4"/>
      <!-- Dynamic Island -->
      <rect x="133" y="44" width="34" height="10" rx="5" fill="#000"/>
      <!-- Lockscreen Clock -->
      <text x="150" y="90" font-family="system-ui, sans-serif" font-size="24" font-weight="800" fill="#FFF" text-anchor="middle">09:41</text>
      <text x="150" y="105" font-family="system-ui, sans-serif" font-size="8" font-weight="500" fill="#A5B4FC" text-anchor="middle">Среда, 3 октября</text>
    </svg>',

    // Laptop
    'laptop-macbook.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Screen Lid -->
      <rect x="60" y="55" width="180" height="120" rx="8" fill="#1E293B" stroke="#334155" stroke-width="2"/>
      <!-- Display -->
      <rect x="66" y="61" width="168" height="108" rx="4" fill="#020617"/>
      <!-- Gradient Screen Content -->
      <rect x="68" y="63" width="164" height="104" rx="3" fill="#0F172A"/>
      <circle cx="150" cy="115" r="30" fill="#3B82F6" opacity="0.3"/>
      <!-- Notch -->
      <rect x="143" y="61" width="14" height="5" rx="2" fill="#000"/>
      <!-- Base Chassis -->
      <polygon points="40,195 260,195 240,175 60,175" fill="#CBD5E1"/>
      <rect x="35" y="195" width="230" height="8" rx="4" fill="#94A3B8"/>
      <!-- Trackpad notch -->
      <rect x="135" y="194" width="30" height="2" rx="1" fill="#475569"/>
    </svg>',

    // Monitor
    'monitor-lg.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Monitor Screen Frame -->
      <rect x="35" y="45" width="230" height="145" rx="6" fill="#0F172A" stroke="#334155" stroke-width="3"/>
      <!-- Display Area -->
      <rect x="40" y="50" width="220" height="135" rx="2" fill="#020617"/>
      <polygon points="40,185 260,185 260,90 40,140" fill="#1E1B4B" opacity="0.5"/>
      <circle cx="150" cy="115" r="25" fill="#EF4444" opacity="0.6"/>
      <!-- Stand Stem -->
      <rect x="144" y="190" width="12" height="45" fill="#475569"/>
      <!-- Stand Base V-Shape -->
      <polygon points="100,245 150,230 200,245 190,250 150,237 110,250" fill="#334155"/>
      <!-- Gaming Badge -->
      <text x="150" y="180" font-family="system-ui, sans-serif" font-size="7" font-weight="800" fill="#FFF" text-anchor="middle">UltraGear 165Hz</text>
    </svg>',

    // Headphones
    'headphones-sony.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Headband Arc -->
      <path d="M 75 160 A 75 75 0 0 1 225 160" fill="none" stroke="#1E293B" stroke-width="14" stroke-linecap="round"/>
      <path d="M 85 140 A 65 65 0 0 1 215 140" fill="none" stroke="#334155" stroke-width="4"/>
      <!-- Left Ear Cup -->
      <rect x="55" y="150" width="30" height="65" rx="15" fill="#0F172A" stroke="#334155" stroke-width="3"/>
      <rect x="75" y="155" width="10" height="55" rx="5" fill="#475569"/>
      <!-- Right Ear Cup -->
      <rect x="215" y="150" width="30" height="65" rx="15" fill="#0F172A" stroke="#334155" stroke-width="3"/>
      <rect x="215" y="155" width="10" height="55" rx="5" fill="#475569"/>
      <!-- Sony Gold Logo text -->
      <text x="150" y="90" font-family="system-ui, sans-serif" font-size="12" font-weight="900" fill="#EAB308" text-anchor="middle">SONY</text>
      <text x="150" y="240" font-family="system-ui, sans-serif" font-size="11" font-weight="700" fill="#475569" text-anchor="middle">WH-1000XM5 ANC</text>
    </svg>',

    // Robot Vacuum
    'vacuum-roborock.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Vacuum Circular Base -->
      <circle cx="150" cy="150" r="95" fill="#FFF" stroke="#E2E8F0" stroke-width="4"/>
      <circle cx="150" cy="150" r="90" fill="#F8FAFC" stroke="#CBD5E1" stroke-width="2"/>
      <!-- Bumper Arc -->
      <path d="M 60 140 A 90 90 0 0 1 240 140" fill="none" stroke="#94A3B8" stroke-width="4"/>
      <!-- Lidar Turret -->
      <circle cx="150" cy="125" r="28" fill="#FFF" stroke="#CBD5E1" stroke-width="3"/>
      <circle cx="150" cy="125" r="20" fill="#EA580C"/>
      <rect x="145" y="120" width="10" height="10" rx="2" fill="#FFF"/>
      <!-- Control Buttons -->
      <circle cx="150" cy="75" r="6" fill="#3B82F6"/>
      <circle cx="135" cy="77" r="4" fill="#94A3B8"/>
      <circle cx="165" cy="77" r="4" fill="#94A3B8"/>
      <!-- Roborock Logo -->
      <text x="150" y="195" font-family="system-ui, sans-serif" font-size="12" font-weight="900" fill="#1E293B" text-anchor="middle">roborock</text>
      <text x="150" y="210" font-family="system-ui, sans-serif" font-size="10" font-weight="700" fill="#64748B" text-anchor="middle">S8 Pro Ultra</text>
    </svg>',

    // Smartwatch
    'watch-apple.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Strap Top & Bottom -->
      <rect x="110" y="20" width="80" height="60" rx="6" fill="#F97316"/>
      <rect x="110" y="220" width="80" height="60" rx="6" fill="#F97316"/>
      <!-- Watch Chassis -->
      <rect x="90" y="65" width="120" height="170" rx="30" fill="#0F172A" stroke="#334155" stroke-width="3"/>
      <!-- Digital Crown -->
      <rect x="210" y="100" width="7" height="30" rx="3" fill="#64748B"/>
      <!-- Screen Display -->
      <rect x="96" y="71" width="108" height="158" rx="24" fill="#000"/>
      <!-- Ring Activity Metrics -->
      <circle cx="150" cy="140" r="38" fill="none" stroke="#EF4444" stroke-width="6"/>
      <circle cx="150" cy="140" r="28" fill="none" stroke="#22C55E" stroke-width="6"/>
      <circle cx="150" cy="140" r="18" fill="none" stroke="#38BDF8" stroke-width="6"/>
      <text x="150" y="145" font-family="system-ui, sans-serif" font-size="12" font-weight="800" fill="#FFF" text-anchor="middle">09:41</text>
    </svg>',

    // Cooler
    'cooler-ak620.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Dual Tower Fin Stack -->
      <rect x="50" y="70" width="90" height="140" rx="4" fill="#334155" stroke="#1E293B" stroke-width="2"/>
      <rect x="160" y="70" width="90" height="140" rx="4" fill="#334155" stroke="#1E293B" stroke-width="2"/>
      <!-- Fin lines -->
      <line x1="50" y1="90" x2="140" y2="90" stroke="#475569" stroke-width="2"/>
      <line x1="50" y1="110" x2="140" y2="110" stroke="#475569" stroke-width="2"/>
      <line x1="50" y1="130" x2="140" y2="130" stroke="#475569" stroke-width="2"/>
      <line x1="160" y1="90" x2="250" y2="90" stroke="#475569" stroke-width="2"/>
      <line x1="160" y1="110" x2="250" y2="110" stroke="#475569" stroke-width="2"/>
      <line x1="160" y1="130" x2="250" y2="130" stroke="#475569" stroke-width="2"/>
      <!-- Copper Heatpipes -->
      <path d="M 60 210 L 60 245 L 240 245 L 240 210" fill="none" stroke="#D97706" stroke-width="6"/>
      <rect x="110" y="240" width="80" height="15" rx="3" fill="#94A3B8"/>
      <!-- Digital Display Cap -->
      <rect x="70" y="55" width="160" height="25" rx="4" fill="#000" stroke="#00E5FF"/>
      <text x="150" y="72" font-family="monospace" font-size="12" font-weight="900" fill="#00E5FF" text-anchor="middle">42 °C</text>
    </svg>',

    // Case
    'case-lianli.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Chassis Body -->
      <rect x="65" y="40" width="170" height="220" rx="8" fill="#18181B" stroke="#27272A" stroke-width="2"/>
      <!-- Tempered Glass Panel -->
      <rect x="75" y="50" width="115" height="200" rx="4" fill="#09090B" stroke="#3F3F46"/>
      <!-- Inside RGB fans visible through glass -->
      <circle cx="132" cy="90" r="22" fill="none" stroke="#06B6D4" stroke-width="3"/>
      <circle cx="132" cy="145" r="22" fill="none" stroke="#06B6D4" stroke-width="3"/>
      <circle cx="132" cy="200" r="22" fill="none" stroke="#06B6D4" stroke-width="3"/>
      <!-- Front I/O Panel -->
      <rect x="200" y="50" width="25" height="200" fill="#27272A"/>
      <circle cx="212" cy="70" r="5" fill="#3B82F6"/>
      <rect x="208" y="85" width="8" height="4" rx="1" fill="#71717A"/>
      <rect x="208" y="95" width="8" height="4" rx="1" fill="#71717A"/>
    </svg>',

    // Power Supply
    'psu-corsair.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Metal Box -->
      <rect x="50" y="70" width="200" height="160" rx="8" fill="#18181B" stroke="#27272A" stroke-width="2"/>
      <!-- Fan Grill -->
      <circle cx="130" cy="150" r="55" fill="#09090B" stroke="#3F3F46" stroke-width="2"/>
      <circle cx="130" cy="150" r="18" fill="#27272A"/>
      <!-- Corsair Sails / Gold 80 Plus Badge -->
      <rect x="195" y="85" width="45" height="50" rx="4" fill="#EAB308"/>
      <text x="217" y="105" font-family="system-ui, sans-serif" font-size="9" font-weight="900" fill="#000" text-anchor="middle">80 PLUS</text>
      <text x="217" y="120" font-family="system-ui, sans-serif" font-size="10" font-weight="900" fill="#000" text-anchor="middle">GOLD</text>
      <text x="130" y="215" font-family="system-ui, sans-serif" font-size="12" font-weight="900" fill="#FFF" text-anchor="middle">RM850x 850W</text>
    </svg>',

    // TV
    'tv-xiaomi.svg' => '
    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 300 300" width="100%" height="100%">
      <rect width="300" height="300" rx="16" fill="#F8FAFC"/>
      <!-- Ultra-thin Bezels -->
      <rect x="25" y="55" width="250" height="150" rx="4" fill="#1E293B" stroke="#0F172A" stroke-width="2"/>
      <!-- Screen 4K -->
      <rect x="28" y="58" width="244" height="144" rx="2" fill="#020617"/>
      <!-- Scenic Vibrant Gradient -->
      <rect x="30" y="60" width="240" height="140" rx="1" fill="#1E1B4B"/>
      <circle cx="150" cy="130" r="45" fill="#F59E0B" opacity="0.7"/>
      <!-- Legs -->
      <polygon points="50,205 60,205 75,235 65,235" fill="#475569"/>
      <polygon points="250,205 240,205 225,235 235,235" fill="#475569"/>
      <text x="150" y="195" font-family="system-ui, sans-serif" font-size="8" font-weight="700" fill="#CBD5E1" text-anchor="middle">Xiaomi TV A Pro 55″ 4K UHD</text>
    </svg>'
];

foreach ($products as $filename => $svgContent) {
    file_put_contents($dir . '/' . $filename, trim($svgContent));
}

echo "Created " . count($products) . " authentic product SVG images in: {$dir}\n";
