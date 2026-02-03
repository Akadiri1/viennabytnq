<?php
$cookie_name = 'cart_token';
if (!isset($_COOKIE[$cookie_name])) {
    header("Location: /cart"); // Redirect to cart if there's no token/cart
    exit;
}
$cartToken = $_COOKIE[$cookie_name];

// --- CONFIGURATION & SETUP ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function getLiveUsdToNgnRate() {
    // Try multiple free exchange rate APIs
    $apis = [
        'https://open.er-api.com/v6/latest/USD',
        'https://api.exchangerate-api.com/v4/latest/USD'
    ];
    
    foreach ($apis as $apiUrl) {
        $context = stream_context_create(['http' => ['timeout' => 5]]);
        $response = @file_get_contents($apiUrl, false, $context);
        if ($response) {
            $data = json_decode($response, true);
            if (isset($data['rates']['NGN'])) {
                return floatval($data['rates']['NGN']);
            }
        }
    }
    
    // Fallback in case all APIs fail - use current market rate (Jan 2025)
    return 1480; // Current market rate (Jan 2025)
}

// Define exchange rate constant (only once)
if (!defined('USD_EXCHANGE_RATE')) {
    define('USD_EXCHANGE_RATE', getLiveUsdToNgnRate());
}

// NEW CONFIGURATION: Map Country Codes to Currency Codes (Expanded for your list)
$countryCurrencyMap = [
    'NG' => 'NGN', // Nigeria Naira
    'US' => 'USD', // United States Dollar
    'GB' => 'GBP', // United Kingdom Pound
    'CA' => 'CAD', // Canada Dollar
    'AU' => 'AUD', // Australia Dollar
    'DE' => 'EUR', // Germany Euro
    'FR' => 'EUR', // France Euro
    'AE' => 'AED', // UAE Dirham
    'SA' => 'SAR', // Saudi Riyal
    'AF' => 'AFN', // Afghanistan
    'AL' => 'ALL', // Albania
    'DZ' => 'DZD', // Algeria
    'AS' => 'USD', // American Samoa
    'AD' => 'EUR', // Andorra
    'AO' => 'AOA', // Angola
    'AR' => 'ARS', // Argentina
    'AT' => 'EUR', // Austria
    'BD' => 'BDT', // Bangladesh
    'BE' => 'EUR', // Belgium
    'BR' => 'BRL', // Brazil
    'CN' => 'CNY', // China
    'DK' => 'DKK', // Denmark
    'EG' => 'EGP', // Egypt
    'GH' => 'GHS', // Ghana
    'IN' => 'INR', // India
    'ID' => 'IDR', // Indonesia
    'IE' => 'EUR', // Ireland
    'IT' => 'EUR', // Italy
    'JP' => 'JPY', // Japan
    'KE' => 'KES', // Kenya
    'MY' => 'MYR', // Malaysia
    'MX' => 'MXN', // Mexico
    'NL' => 'EUR', // Netherlands
    'NZ' => 'NZD', // New Zealand
    'PK' => 'PKR', // Pakistan
    'PH' => 'PHP', // Philippines
    'PL' => 'PLN', // Poland
    'PT' => 'EUR', // Portugal
    'RU' => 'RUB', // Russia
    'SG' => 'SGD', // Singapore
    'ZA' => 'ZAR', // South Africa
    'KR' => 'KRW', // South Korea
    'ES' => 'EUR', // Spain
    'SE' => 'SEK', // Sweden
    'CH' => 'CHF', // Switzerland
    'TH' => 'THB', // Thailand
    'TR' => 'TRY', // Turkey
    // Add any others if needed
];

// Phone Number Validation Configuration
$phoneValidationRules = [
    'NG' => ['prefix' => '+234', 'pattern' => '/^(\+234|0)?[789][01]\d{8}$/', 'placeholder' => '+234 801 234 5678', 'example' => '+234 801 234 5678'],
    'US' => ['prefix' => '+1', 'pattern' => '/^(\+1|1)?[2-9]\d{2}[2-9]\d{2}\d{4}$/', 'placeholder' => '+1 (555) 123-4567', 'example' => '+1 (555) 123-4567'],
    'GB' => ['prefix' => '+44', 'pattern' => '/^(\+44|0)?[1-9]\d{8,9}$/', 'placeholder' => '+44 20 7946 0958', 'example' => '+44 20 7946 0958'],
    'CA' => ['prefix' => '+1', 'pattern' => '/^(\+1|1)?[2-9]\d{2}[2-9]\d{2}\d{4}$/', 'placeholder' => '+1 (416) 555-0123', 'example' => '+1 (416) 555-0123'],
    'AU' => ['prefix' => '+61', 'pattern' => '/^(\+61|0)?[2-9]\d{8}$/', 'placeholder' => '+61 2 1234 5678', 'example' => '+61 2 1234 5678'],
    'DE' => ['prefix' => '+49', 'pattern' => '/^(\+49|0)?[1-9]\d{10,11}$/', 'placeholder' => '+49 30 12345678', 'example' => '+49 30 12345678'],
    'FR' => ['prefix' => '+33', 'pattern' => '/^(\+33|0)?[1-9]\d{8}$/', 'placeholder' => '+33 1 23 45 67 89', 'example' => '+33 1 23 45 67 89'],
    'AE' => ['prefix' => '+971', 'pattern' => '/^(\+971|0)?[2-9]\d{8}$/', 'placeholder' => '+971 4 123 4567', 'example' => '+971 4 123 4567'],
    'SA' => ['prefix' => '+966', 'pattern' => '/^(\+966|0)?[1-9]\d{8}$/', 'placeholder' => '+966 11 123 4567', 'example' => '+966 11 123 4567'],
    'AF' => ['prefix' => '+93', 'pattern' => '/^(\+93|0)?[1-9]\d{8}$/', 'placeholder' => '+93 20 123 4567', 'example' => '+93 20 123 4567'],
    'AL' => ['prefix' => '+355', 'pattern' => '/^(\+355|0)?[1-9]\d{7,8}$/', 'placeholder' => '+355 4 123 4567', 'example' => '+355 4 123 4567'],
    'DZ' => ['prefix' => '+213', 'pattern' => '/^(\+213|0)?[1-9]\d{8}$/', 'placeholder' => '+213 21 12 34 56', 'example' => '+213 21 12 34 56'],
    'AR' => ['prefix' => '+54', 'pattern' => '/^(\+54|0)?[1-9]\d{9,10}$/', 'placeholder' => '+54 11 1234-5678', 'example' => '+54 11 1234-5678'],
    'AT' => ['prefix' => '+43', 'pattern' => '/^(\+43|0)?[1-9]\d{9,10}$/', 'placeholder' => '+43 1 234 5678', 'example' => '+43 1 234 5678'],
    'BD' => ['prefix' => '+880', 'pattern' => '/^(\+880|0)?[1-9]\d{9}$/', 'placeholder' => '+880 2 1234 5678', 'example' => '+880 2 1234 5678'],
    'BE' => ['prefix' => '+32', 'pattern' => '/^(\+32|0)?[1-9]\d{8}$/', 'placeholder' => '+32 2 123 45 67', 'example' => '+32 2 123 45 67'],
    'BR' => ['prefix' => '+55', 'pattern' => '/^(\+55|0)?[1-9]\d{10,11}$/', 'placeholder' => '+55 11 91234-5678', 'example' => '+55 11 91234-5678'],
    'CN' => ['prefix' => '+86', 'pattern' => '/^(\+86|0)?1[3-9]\d{9}$/', 'placeholder' => '+86 138 0013 8000', 'example' => '+86 138 0013 8000'],
    'DK' => ['prefix' => '+45', 'pattern' => '/^(\+45|0)?[1-9]\d{7,8}$/', 'placeholder' => '+45 12 34 56 78', 'example' => '+45 12 34 56 78'],
    'EG' => ['prefix' => '+20', 'pattern' => '/^(\+20|0)?[1-9]\d{9}$/', 'placeholder' => '+20 2 1234 5678', 'example' => '+20 2 1234 5678'],
    'GH' => ['prefix' => '+233', 'pattern' => '/^(\+233|0)?[2-9]\d{8}$/', 'placeholder' => '+233 24 123 4567', 'example' => '+233 24 123 4567'],
    'IN' => ['prefix' => '+91', 'pattern' => '/^(\+91|0)?[6-9]\d{9}$/', 'placeholder' => '+91 98765 43210', 'example' => '+91 98765 43210'],
    'ID' => ['prefix' => '+62', 'pattern' => '/^(\+62|0)?[2-9]\d{8,11}$/', 'placeholder' => '+62 21 1234 5678', 'example' => '+62 21 1234 5678'],
    'IE' => ['prefix' => '+353', 'pattern' => '/^(\+353|0)?[1-9]\d{8,9}$/', 'placeholder' => '+353 1 234 5678', 'example' => '+353 1 234 5678'],
    'IT' => ['prefix' => '+39', 'pattern' => '/^(\+39|0)?[1-9]\d{8,10}$/', 'placeholder' => '+39 06 1234 5678', 'example' => '+39 06 1234 5678'],
    'JP' => ['prefix' => '+81', 'pattern' => '/^(\+81|0)?[1-9]\d{9,10}$/', 'placeholder' => '+81 3 1234 5678', 'example' => '+81 3 1234 5678'],
    'KE' => ['prefix' => '+254', 'pattern' => '/^(\+254|0)?[1-9]\d{8}$/', 'placeholder' => '+254 20 123 4567', 'example' => '+254 20 123 4567'],
    'MY' => ['prefix' => '+60', 'pattern' => '/^(\+60|0)?[1-9]\d{8,9}$/', 'placeholder' => '+60 3 1234 5678', 'example' => '+60 3 1234 5678'],
    'MX' => ['prefix' => '+52', 'pattern' => '/^(\+52|0)?[1-9]\d{9,10}$/', 'placeholder' => '+52 55 1234 5678', 'example' => '+52 55 1234 5678'],
    'NL' => ['prefix' => '+31', 'pattern' => '/^(\+31|0)?[1-9]\d{8,9}$/', 'placeholder' => '+31 20 123 4567', 'example' => '+31 20 123 4567'],
    'NZ' => ['prefix' => '+64', 'pattern' => '/^(\+64|0)?[1-9]\d{8,9}$/', 'placeholder' => '+64 9 123 4567', 'example' => '+64 9 123 4567'],
    'PK' => ['prefix' => '+92', 'pattern' => '/^(\+92|0)?[1-9]\d{9}$/', 'placeholder' => '+92 21 1234 5678', 'example' => '+92 21 1234 5678'],
    'PH' => ['prefix' => '+63', 'pattern' => '/^(\+63|0)?[1-9]\d{9}$/', 'placeholder' => '+63 2 123 4567', 'example' => '+63 2 123 4567'],
    'PL' => ['prefix' => '+48', 'pattern' => '/^(\+48|0)?[1-9]\d{8}$/', 'placeholder' => '+48 22 123 45 67', 'example' => '+48 22 123 45 67'],
    'PT' => ['prefix' => '+351', 'pattern' => '/^(\+351|0)?[1-9]\d{8}$/', 'placeholder' => '+351 21 123 4567', 'example' => '+351 21 123 4567'],
    'RU' => ['prefix' => '+7', 'pattern' => '/^(\+7|0)?[1-9]\d{9,10}$/', 'placeholder' => '+7 495 123 45 67', 'example' => '+7 495 123 45 67'],
    'SG' => ['prefix' => '+65', 'pattern' => '/^(\+65|0)?[689]\d{7}$/', 'placeholder' => '+65 6123 4567', 'example' => '+65 6123 4567'],
    'ZA' => ['prefix' => '+27', 'pattern' => '/^(\+27|0)?[1-9]\d{8,9}$/', 'placeholder' => '+27 11 123 4567', 'example' => '+27 11 123 4567'],
    'KR' => ['prefix' => '+82', 'pattern' => '/^(\+82|0)?[1-9]\d{8,9}$/', 'placeholder' => '+82 2 1234 5678', 'example' => '+82 2 1234 5678'],
    'ES' => ['prefix' => '+34', 'pattern' => '/^(\+34|0)?[6-9]\d{8}$/', 'placeholder' => '+34 612 34 56 78', 'example' => '+34 612 34 56 78'],
    'SE' => ['prefix' => '+46', 'pattern' => '/^(\+46|0)?[1-9]\d{8,9}$/', 'placeholder' => '+46 8 123 456 78', 'example' => '+46 8 123 456 78'],
    'CH' => ['prefix' => '+41', 'pattern' => '/^(\+41|0)?[1-9]\d{8}$/', 'placeholder' => '+41 44 123 45 67', 'example' => '+41 44 123 45 67'],
    'TH' => ['prefix' => '+66', 'pattern' => '/^(\+66|0)?[1-9]\d{8,9}$/', 'placeholder' => '+66 2 123 4567', 'example' => '+66 2 123 4567'],
    'TR' => ['prefix' => '+90', 'pattern' => '/^(\+90|0)?[1-9]\d{9}$/', 'placeholder' => '+90 212 123 45 67', 'example' => '+90 212 123 45 67'],
];

// Set the active currency
if (isset($_SESSION['currency'])) {
    $current_currency = $_SESSION['currency'];
} elseif (isset($_COOKIE['user_currency'])) {
    $current_currency = $_COOKIE['user_currency'];
} else {
    // Default to NGN
    $current_currency = $countryCurrencyMap['NG'] ?? 'USD';
}

$cookie_name = 'cart_token';
if (!isset($_COOKIE[$cookie_name])) {
    // header("Location: /view-cart"); // Redirect to view-cart if there's no token/cart
    // exit;
}
$cartToken = $_COOKIE[$cookie_name] ?? 'MOCK_TOKEN';

// --- MOCK DATABASE SETUP (Ensure this is replaced with your actual DB connection) ---
// --- DATABASE CHECK ---
// Ensure we are using the real connection provided by index.php
if (!isset($conn)) {
    // Should not happen if accessed via router, but safety fallback logic could go here if needed.
    // For now, we assume $conn exists.
}

$cartSql = "
    SELECT 
        ci.*, 
        pp.name AS product_name, 
        pp.image_one AS product_image,
        ci.color_name,
        ci.custom_color_name,
        ci.size_name,
        ci.custom_size_details
    FROM cart_items ci
    JOIN panel_products pp ON ci.product_id = pp.id
    WHERE ci.cart_token = ?
";
$cartStmt = $conn->prepare($cartSql);
$cartStmt->execute([$cartToken]);
$cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($cartItems)) {
    header("Location: /shop"); 
    exit;
}

$subtotal = array_sum(array_column($cartItems, 'total_price'));

// Fetch shipping locations for the dropdown (Nigeria only for now)
$shippingLocations = selectContent($conn, "shipping_fees", ['is_active' => TRUE]);

// Function to get country-specific shipping fees
function getCountryShippingFees($conn, $countryCode) {
    // This function will fetch shipping fees for a specific country
    // You'll need to modify your database structure to include country_code column
    $sql = "SELECT * FROM shipping_fees WHERE country_code = ? AND is_active = 1 ORDER BY fee ASC";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$countryCode]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Assuming these variables are defined elsewhere for your header/footer
$logo_directory = $logo_directory ?? 'images/logo.png'; 
$site_name = $site_name ?? 'Your Site Name'; 

// --- INCLUDE COUNTRIES & STATES DATA ---
// Replace the mock array below with your actual file include:
$countriesData = [
    'AF' => ['name' => 'Afghanistan', 'states' => ['BDS' => 'Badakhshan', 'BGL' => 'Baghlan', 'BAL' => 'Balkh', 'BDG' => 'Badghis', 'BAM' => 'Bamyan', 'DAY' => 'Daykundi', 'FRA' => 'Farah', 'FYB' => 'Faryab', 'GHA' => 'Ghazni', 'GHO' => 'Ghor', 'HEL' => 'Helmand', 'HER' => 'Herat', 'JOW' => 'Jowzjan', 'KAB' => 'Kabul', 'KAN' => 'Kandahar', 'KAP' => 'Kapisa', 'KHO' => 'Khost', 'KNR' => 'Kunar', 'KDZ' => 'Kunduz', 'LAG' => 'Laghman', 'LOG' => 'Logar', 'NAN' => 'Nangarhar', 'NIM' => 'Nimruz', 'NUR' => 'Nuristan', 'PKA' => 'Paktika', 'PIA' => 'Paktia', 'PAR' => 'Parwan', 'PAN' => 'Panjshir', 'SAM' => 'Samangan', 'SAR' => 'Sar-e Pol', 'TAK' => 'Takhar', 'URU' => 'Urozgan', 'WAR' => 'Maidan Wardak', 'ZAB' => 'Zabul']],
    'AL' => ['name' => 'Albania', 'states' => ['01' => 'Berat', '09' => 'Dibër', '02' => 'Durrës', '03' => 'Elbasan', '04' => 'Fier', '05' => 'Gjirokastër', '06' => 'Korçë', '07' => 'Kukës', '08' => 'Lezhë', '10' => 'Shkodër', '11' => 'Tirana', '12' => 'Vlorë']],
    'DZ' => ['name' => 'Algeria', 'states' => []], 
    'AS' => ['name' => 'American Samoa', 'states' => []],
    'AD' => ['name' => 'Andorra', 'states' => []],
    'AO' => ['name' => 'Angola', 'states' => []],
    'AR' => ['name' => 'Argentina', 'states' => ['B' => 'Buenos Aires', 'C' => 'Ciudad Autónoma de Buenos Aires', 'K' => 'Catamarca', 'H' => 'Chaco', 'U' => 'Chubut', 'W' => 'Corrientes', 'X' => 'Córdoba', 'E' => 'Entre Ríos', 'P' => 'Formosa', 'Y' => 'Jujuy', 'L' => 'La Pampa', 'F' => 'La Rioja', 'M' => 'Mendoza', 'N' => 'Misiones', 'Q' => 'Neuquén', 'R' => 'Río Negro', 'A' => 'Salta', 'J' => 'San Juan', 'D' => 'San Luis', 'Z' => 'Santa Cruz', 'S' => 'Santa Fe', 'G' => 'Santiago del Estero', 'V' => 'Tierra del Fuego', 'T' => 'Tucumán']],
    'AU' => ['name' => 'Australia', 'states' => ['ACT' => 'Australian Capital Territory', 'NSW' => 'New South Wales', 'NT' => 'Northern Territory', 'QLD' => 'Queensland', 'SA' => 'South Australia', 'TAS' => 'Tasmania', 'VIC' => 'Victoria', 'WA' => 'Western Australia']],
    'AT' => ['name' => 'Austria', 'states' => ['1' => 'Burgenland', '2' => 'Carinthia', '3' => 'Lower Austria', '4' => 'Upper Austria', '5' => 'Salzburg', '6' => 'Styria', '7' => 'Tyrol', '8' => 'Vorarlberg', '9' => 'Vienna']],
    'BD' => ['name' => 'Bangladesh', 'states' => ['05' => 'Bagerhat', '01' => 'Bandarban', '02' => 'Barguna', '06' => 'Barishal', '07' => 'Bhola', '03' => 'Bogura', '04' => 'Brahmanbaria', '09' => 'Chandpur', '10' => 'Chattogram', '12' => 'Chuadanga', '11' => "Cox's Bazar", '08' => 'Cumilla', '13' => 'Dhaka', '14' => 'Dinajpur', '15' => 'Faridpur', '16' => 'Feni', '18' => 'Gaibandha', '19' => 'Gazipur', '17' => 'Gopalganj', '20' => 'Habiganj', '21' => 'Jamalpur', '22' => 'Jashore', '25' => 'Jhalokati', '23' => 'Jhenaidah', '24' => 'Joypurhat', '29' => 'Khagrachhari', '27' => 'Khulna', '26' => 'Kishoreganj', '28' => 'Kurigram', '30' => 'Kushtia', '31' => 'Lakshmipur', '32' => 'Lalmonirhat', '36' => 'Madaripur', '37' => 'Magura', '33' => 'Manikganj', '39' => 'Meherpur', '38' => 'Moulvibazar', '35' => 'Munshiganj', '34' => 'Mymensingh', '48' => 'Naogaon', '43' => 'Narail', '40' => 'Narayanganj', '42' => 'Narsingdi', '44' => 'Natore', '45' => 'Nawabganj', '41' => 'Netrakona', '46' => 'Nilphamari', '47' => 'Noakhali', '49' => 'Pabna', '52' => 'Panchagarh', '51' => 'Patuakhali', '50' => 'Pirojpur', '53' => 'Rajbari', '54' => 'Rajshahi', '56' => 'Rangamati', '55' => 'Rangpur', '58' => 'Satkhira', '62' => 'Shariatpur', '57' => 'Sherpur', '59' => 'Sirajganj', '61' => 'Sunamganj', '60' => 'Sylhet', '63' => 'Tangail', '64' => 'Thakurgaon']],
    'BE' => ['name' => 'Belgium', 'states' => ['BRU' => 'Brussels-Capital Region', 'VLG' => 'Flanders', 'WAL' => 'Wallonia']],
    'BR' => ['name' => 'Brazil', 'states' => ['AC' => 'Acre', 'AL' => 'Alagoas', 'AP' => 'Amapá', 'AM' => 'Amazonas', 'BA' => 'Bahia', 'CE' => 'Ceará', 'DF' => 'Distrito Federal', 'ES' => 'Espírito Santo', 'GO' => 'Goiás', 'MA' => 'Maranhão', 'MT' => 'Mato Grosso', 'MS' => 'Mato Grosso do Sul', 'MG' => 'Minas Gerais', 'PA' => 'Pará', 'PB' => 'Paraíba', 'PR' => 'Paraná', 'PE' => 'Pernambuco', 'PI' => 'Piauí', 'RJ' => 'Rio de Janeiro', 'RN' => 'Rio Grande do Norte', 'RS' => 'Rio Grande do Sul', 'RO' => 'Rondônia', 'RR' => 'Roraima', 'SC' => 'Santa Catarina', 'SP' => 'São Paulo', 'SE' => 'Sergipe', 'TO' => 'Tocantins']],
    'CA' => ['name' => 'Canada', 'states' => ['AB' => 'Alberta', 'BC' => 'British Columbia', 'MB' => 'Manitoba', 'NB' => 'New Brunswick', 'NL' => 'Newfoundland and Labrador', 'NT' => 'Northwest Territories', 'NS' => 'Nova Scotia', 'NU' => 'Nunavut', 'ON' => 'Ontario', 'PE' => 'Prince Edward Island', 'QC' => 'Quebec', 'SK' => 'Saskatchewan', 'YT' => 'Yukon']],
    'CN' => ['name' => 'China', 'states' => ['AH' => 'Anhui', 'BJ' => 'Beijing', 'CQ' => 'Chongqing', 'FJ' => 'Fujian', 'GS' => 'Gansu', 'GD' => 'Guangdong', 'GX' => 'Guangxi', 'GZ' => 'Guizhou', 'HI' => 'Hainan', 'HE' => 'Hebei', 'HL' => 'Heilongjiang', 'HA' => 'Henan', 'HK' => 'Hong Kong', 'HB' => 'Hubei', 'HN' => 'Hunan', 'JS' => 'Jiangsu', 'JX' => 'Jiangxi', 'JL' => 'Jilin', 'LN' => 'Liaoning', 'MO' => 'Macau', 'NM' => 'Nei Mongol', 'NX' => 'Ningxia', 'QH' => 'Qinghai', 'SN' => 'Shaanxi', 'SD' => 'Shandong', 'SH' => 'Shanghai', 'SX' => 'Shanxi', 'SC' => 'Sichuan', 'TW' => 'Taiwan', 'TJ' => 'Tianjin', 'XZ' => 'Xizang', 'XJ' => 'Xinjiang', 'YN' => 'Yunnan', 'ZJ' => 'Zhejiang']],
    'DK' => ['name' => 'Denmark', 'states' => []],
    'EG' => ['name' => 'Egypt', 'states' => []],
    'FR' => ['name' => 'France', 'states' => ['ARA' => 'Auvergne-Rhône-Alpes', 'BFC' => 'Bourgogne-Franche-Comté', 'BRE' => 'Brittany', 'CVL' => 'Centre-Val de Loire', 'COR' => 'Corsica', 'GES' => 'Grand Est', 'HDF' => 'Hauts-de-France', 'IDF' => 'Île-de-France', 'NOR' => 'Normandy', 'NAQ' => 'Nouvelle-Aquitaine', 'OCC' => 'Occitanie', 'PDL' => 'Pays de la Loire', 'PAC' => 'Provence-Alpes-Côte d\'Azur']],
    'DE' => ['name' => 'Germany', 'states' => ['BW' => 'Baden-Württemberg', 'BY' => 'Bavaria', 'BE' => 'Berlin', 'BB' => 'Brandenburg', 'HB' => 'Bremen', 'HH' => 'Hamburg', 'HE' => 'Hesse', 'MV' => 'Mecklenburg-Vorpommern', 'NI' => 'Lower Saxony', 'NW' => 'North Rhine-Westphalia', 'RP' => 'Rhineland-Palatinate', 'SL' => 'Saarland', 'SN' => 'Saxony', 'ST' => 'Saxony-Anhalt', 'SH' => 'Schleswig-Holstein', 'TH' => 'Thuringia']],
    'GH' => ['name' => 'Ghana', 'states' => ['AH' => 'Ahafo', 'AF' => 'Ashanti', 'BO' => 'Bono', 'BE' => 'Bono East', 'CP' => 'Central', 'EP' => 'Eastern', 'AA' => 'Greater Accra', 'NE' => 'North East', 'NP' => 'Northern', 'OT' => 'Oti', 'SV' => 'Savannah', 'UE' => 'Upper East', 'UW' => 'Upper West', 'TV' => 'Volta', 'WP' => 'Western', 'WN' => 'Western North']],
    'IN' => ['name' => 'India', 'states' => ['AN' => 'Andaman and Nicobar Islands', 'AP' => 'Andhra Pradesh', 'AR' => 'Arunachal Pradesh', 'AS' => 'Assam', 'BR' => 'Bihar', 'CH' => 'Chandigarh', 'CT' => 'Chhattisgarh', 'DN' => 'Dadra and Nagar Haveli and Daman and Diu', 'DL' => 'Delhi', 'GA' => 'Goa', 'GJ' => 'Gujarat', 'HR' => 'Haryana', 'HP' => 'Himachal Pradesh', 'JK' => 'Jammu and Kashmir', 'JH' => 'Jharkhand', 'KA' => 'Karnataka', 'KL' => 'Kerala', 'LA' => 'Ladakh', 'LD' => 'Lakshadweep', 'MP' => 'Madhya Pradesh', 'MH' => 'Maharashtra', 'MN' => 'Manipur', 'ML' => 'Meghalaya', 'MZ' => 'Mizoram', 'NL' => 'Nagaland', 'OR' => 'Odisha', 'PY' => 'Puducherry', 'PB' => 'Punjab', 'RJ' => 'Rajasthan', 'SK' => 'Sikkim', 'TN' => 'Tamil Nadu', 'TG' => 'Telangana', 'TR' => 'Tripura', 'UP' => 'Uttar Pradesh', 'UT' => 'Uttarakhand', 'WB' => 'West Bengal']],
    'ID' => ['name' => 'Indonesia', 'states' => []],
    'IE' => ['name' => 'Ireland', 'states' => ['CW' => 'Carlow', 'CN' => 'Cavan', 'CE' => 'Clare', 'CO' => 'Cork', 'DL' => 'Donegal', 'D' => 'Dublin', 'G' => 'Galway', 'KY' => 'Kerry', 'KE' => 'Kildare', 'KK' => 'Kilkenny', 'LS' => 'Laois', 'LM' => 'Leitrim', 'LK' => 'Limerick', 'LD' => 'Longford', 'LH' => 'Louth', 'MO' => 'Mayo', 'MH' => 'Meath', 'MN' => 'Monaghan', 'OY' => 'Offaly', 'RN' => 'Roscommon', 'SO' => 'Sligo', 'TA' => 'Tipperary', 'WD' => 'Waterford', 'WH' => 'Westmeath', 'WX' => 'Wexford', 'WW' => 'Wicklow']],
    'IT' => ['name' => 'Italy', 'states' => []],
    'JP' => ['name' => 'Japan', 'states' => ['01' => 'Hokkaido', '02' => 'Aomori', '03' => 'Iwate', '04' => 'Miyagi', '05' => 'Akita', '06' => 'Yamagata', '07' => 'Fukushima', '08' => 'Ibaraki', '09' => 'Tochigi', '10' => 'Gunma', '11' => 'Saitama', '12' => 'Chiba', '13' => 'Tokyo', '14' => 'Kanagawa', '15' => 'Niigata', '16' => 'Toyama', '17' => 'Ishikawa', '18' => 'Fukui', '19' => 'Yamanashi', '20' => 'Nagano', '21' => 'Gifu', '22' => 'Shizuoka', '23' => 'Aichi', '24' => 'Mie', '25' => 'Shiga', '26' => 'Kyoto', '27' => 'Osaka', '28' => 'Hyogo', '29' => 'Nara', '30' => 'Wakayama', '31' => 'Tottori', '32' => 'Shimane', '33' => 'Okayama', '34' => 'Hiroshima', '35' => 'Yamaguchi', '36' => 'Tokushima', '37' => 'Kagawa', '38' => 'Ehime', '39' => 'Kochi', '40' => 'Fukuoka', '41' => 'Saga', '42' => 'Nagasaki', '43' => 'Kumamoto', '44' => 'Oita', '45' => 'Miyazaki', '46' => 'Kagoshima', '47' => 'Okinawa']],
    'KE' => ['name' => 'Kenya', 'states' => []],
    'MY' => ['name' => 'Malaysia', 'states' => ['JHR' => 'Johor', 'KDH' => 'Kedah', 'KTN' => 'Kelantan', 'MLK' => 'Melaka', 'NSN' => 'Negeri Sembilan', 'PHG' => 'Pahang', 'PNG' => 'Pulau Pinang', 'PRK' => 'Perak', 'PLS' => 'Perlis', 'SBH' => 'Sabah', 'SWK' => 'Sarawak', 'SGR' => 'Selangor', 'TRG' => 'Terengganu', 'KUL' => 'W.P. Kuala Lumpur', 'LBN' => 'W.P. Labuan', 'PJY' => 'W.P. Putrajaya']],
    'MX' => ['name' => 'Mexico', 'states' => ['AGS' => 'Aguascalientes', 'BC' => 'Baja California', 'BCS' => 'Baja California Sur', 'CAMP' => 'Campeche', 'CHIS' => 'Chiapas', 'CHIH' => 'Chihuahua', 'COAH' => 'Coahuila', 'COL' => 'Colima', 'CDMX' => 'Ciudad de México', 'DGO' => 'Durango', 'GTO' => 'Guanajuato', 'GRO' => 'Guerrero', 'HGO' => 'Hidalgo', 'JAL' => 'Jalisco', 'MEX' => 'México', 'MICH' => 'Michoacán', 'MOR' => 'Morelos', 'NAY' => 'Nayarit', 'NL' => 'Nuevo León', 'OAX' => 'Oaxaca', 'PUE' => 'Puebla', 'QRO' => 'Querétaro', 'Q ROO' => 'Quintana Roo', 'SLP' => 'San Luis Potosí', 'SIN' => 'Sinaloa', 'SON' => 'Sonora', 'TAB' => 'Tabasco', 'TAMPS' => 'Tamaulipas', 'TLAX' => 'Tlaxcala', 'VER' => 'Veracruz', 'YUC' => 'Yucatán', 'ZAC' => 'Zacatecas']],
    'NL' => ['name' => 'Netherlands', 'states' => []],
    'NZ' => ['name' => 'New Zealand', 'states' => ['AUK' => 'Auckland', 'BOP' => 'Bay of Plenty', 'CAN' => 'Canterbury', 'GIS' => 'Gisborne', 'HKB' => "Hawke's Bay", 'MWT' => 'Manawatu-Wanganui', 'MBH' => 'Marlborough', 'NSN' => 'Nelson', 'NTL' => 'Northland', 'OTA' => 'Otago', 'STL' => 'Southland', 'TKI' => 'Taranaki', 'TAS' => 'Tasman', 'WKO' => 'Waikato', 'WGN' => 'Wellington', 'WTC' => 'West Coast']],
    'NG' => ['name' => 'Nigeria', 'states' => ['AB' => 'Abia', 'AD' => 'Adamawa', 'AK' => 'Akwa Ibom', 'AN' => 'Anambra', 'BA' => 'Bauchi', 'BY' => 'Bayelsa', 'BE' => 'Benue', 'BO' => 'Borno', 'CR' => 'Cross River', 'DE' => 'Delta', 'EB' => 'Ebonyi', 'ED' => 'Edo', 'EK' => 'Ekiti', 'EN' => 'Enugu', 'FC' => 'Federal Capital Territory', 'GO' => 'Gombe', 'IM' => 'Imo', 'JI' => 'Jigawa', 'KD' => 'Kaduna', 'KN' => 'Kano', 'KT' => 'Katsina', 'KE' => 'Kebbi', 'KO' => 'Kogi', 'KW' => 'Kwara', 'LA' => 'Lagos', 'NA' => 'Nasarawa', 'NI' => 'Niger', 'OG' => 'Ogun', 'ON' => 'Ondo', 'OS' => 'Osun', 'OY' => 'Oyo', 'PL' => 'Plateau', 'RI' => 'Rivers', 'SO' => 'Sokoto', 'TA' => 'Taraba', 'YO' => 'Yobe', 'ZA' => 'Zamfara']],
    'PK' => ['name' => 'Pakistan', 'states' => ['JK' => 'Azad Jammu and Kashmir', 'BA' => 'Balochistan', 'GB' => 'Gilgit-Baltistan', 'IS' => 'Islamabad Capital Territory', 'KP' => 'Khyber Pakhtunkhwa', 'PB' => 'Punjab', 'SD' => 'Sindh']],
    'PH' => ['name' => 'Philippines', 'states' => []],
    'PL' => ['name' => 'Poland', 'states' => []],
    'PT' => ['name' => 'Portugal', 'states' => []],
    'RU' => ['name' => 'Russia', 'states' => []],
    'SA' => ['name' => 'Saudi Arabia', 'states' => []],
    'SG' => ['name' => 'Singapore', 'states' => []],
    'ZA' => ['name' => 'South Africa', 'states' => ['EC' => 'Eastern Cape', 'FS' => 'Free State', 'GP' => 'Gauteng', 'KZN' => 'KwaZulu-Natal', 'LP' => 'Limpopo', 'MP' => 'Mpumalanga', 'NW' => 'North West', 'NC' => 'Northern Cape', 'WC' => 'Western Cape']],
    'KR' => ['name' => 'South Korea', 'states' => []],
    'ES' => ['name' => 'Spain', 'states' => ['AN' => 'Andalusia', 'AR' => 'Aragon', 'AS' => 'Asturias', 'CN' => 'Canary Islands', 'CB' => 'Cantabria', 'CM' => 'Castile-La Mancha', 'CL' => 'Castile and León', 'CT' => 'Catalonia', 'CE' => 'Ceuta', 'EX' => 'Extremadura', 'GA' => 'Galicia', 'IB' => 'Balearic Islands', 'RI' => 'La Rioja', 'MD' => 'Madrid', 'ML' => 'Melilla', 'MC' => 'Murcia', 'NC' => 'Navarre', 'PV' => 'Basque Country', 'VC' => 'Valencian Community']],
    'SE' => ['name' => 'Sweden', 'states' => []],
    'CH' => ['name' => 'Switzerland', 'states' => []],
    'TH' => ['name' => 'Thailand', 'states' => []],
    'TR' => ['name' => 'Turkey', 'states' => []],
    'AE' => ['name' => 'United Arab Emirates', 'states' => ['AZ' => 'Abu Dhabi', 'AJ' => 'Ajman', 'FU' => 'Fujairah', 'SH' => 'Sharjah', 'DU' => 'Dubai', 'RK' => 'Ras al-Khaimah', 'UQ' => 'Umm al-Quwain']],
    'GB' => ['name' => 'United Kingdom', 'states' => ['ENG' => 'England', 'NIR' => 'Northern Ireland', 'SCT' => 'Scotland', 'WLS' => 'Wales']],
    'US' => ['name' => 'United States', 'states' => ['AL' => 'Alabama', 'AK' => 'Alaska', 'AZ' => 'Arizona', 'AR' => 'Arkansas', 'CA' => 'California', 'CO' => 'Colorado', 'CT' => 'Connecticut', 'DE' => 'Delaware', 'DC' => 'District Of Columbia', 'FL' => 'Florida', 'GA' => 'Georgia', 'HI' => 'Hawaii', 'ID' => 'Idaho', 'IL' => 'Illinois', 'IN' => 'Indiana', 'IA' => 'Iowa', 'KS' => 'Kansas', 'KY' => 'Kentucky', 'LA' => 'Louisiana', 'ME' => 'Maine', 'MD' => 'Maryland', 'MA' => 'Massachusetts', 'MI' => 'Michigan', 'MN' => 'Minnesota', 'MS' => 'Mississippi', 'MO' => 'Missouri', 'MT' => 'Montana', 'NE' => 'Nebraska', 'NV' => 'Nevada', 'NH' => 'New Hampshire', 'NJ' => 'New Jersey', 'NM' => 'New Mexico', 'NY' => 'New York', 'NC' => 'North Carolina', 'ND' => 'North Dakota', 'OH' => 'Ohio', 'OK' => 'Oklahoma', 'OR' => 'Oregon', 'PA' => 'Pennsylvania', 'RI' => 'Rhode Island', 'SC' => 'South Carolina', 'SD' => 'South Dakota', 'TN' => 'Tennessee', 'TX' => 'Texas', 'UT' => 'Utah', 'VT' => 'Vermont', 'VA' => 'Virginia', 'WA' => 'Washington', 'WV' => 'West Virginia', 'WI' => 'Wisconsin', 'WY' => 'Wyoming']],
]; // $countriesData = include 'country_states.php'; // Use this in production
uasort($countriesData, function($a, $b) {
    return strcmp($a['name'], $b['name']);
});

// Get all available country shipping fees for JavaScript
$allCountryShippingFees = [];
$countryCodes = array_keys($countriesData);
foreach ($countryCodes as $code) {
    $fees = getCountryShippingFees($conn, $code);
    if (!empty($fees)) {
        $allCountryShippingFees[$code] = $fees;
    }
}

// Pass the currency map to JavaScript
$jsCountryCurrencyMap = json_encode($countryCurrencyMap);
$jsCurrentCurrency = $current_currency;

// Pass phone validation data to JavaScript
$jsPhoneValidationRules = json_encode($phoneValidationRules);

// Pass shipping data to JavaScript
$jsCountryShippingFees = json_encode($allCountryShippingFees);

?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="shortcut icon" type="image/png" href="<?=$logo_directory?>">
<title><?=$site_name?> | Checkout</title>
<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = { theme: { extend: { colors: { 'brand-bg': '#F9F6F2', 'brand-text': '#1A1A1A', 'brand-gray': '#6B7280', }, fontFamily: { 'sans': ['Inter', 'ui-sans-serif', 'system-ui'], 'serif': ['Cormorant Garamond', 'serif'], } } } };
</script>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@600;700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://unpkg.com/feather-icons"></script>
<script src="https://js.paystack.co/v1/inline.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/toastify-js"></script>
<link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/toastify-js/src/toastify.min.css">
<style>
.form-input-sleek { background-color: transparent; border: 0; border-bottom: 1px solid #d1d5db; border-radius: 0; padding: 0.75rem 0.25rem; width: 100%; transition: border-color 0.2s; -webkit-appearance: none; -moz-appearance: none; appearance: none; } 
.form-input-sleek:focus { outline: none; border-bottom-color: #1A1A1A; }
/* Style for select dropdown arrow */
select.form-input-sleek { background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e"); background-position: right 0.5rem center; background-repeat: no-repeat; background-size: 1.5em 1.5em; padding-right: 2.5rem; }
.step-header { display: flex; align-items: center; gap: 0.75rem; font-size: 1.25rem; font-family: 'Cormorant Garamond', serif; font-weight: 600; margin-bottom: 1.5rem; }
.step-header .step-circle { display: flex; align-items: center; justify-content: center; width: 1.75rem; height: 1.75rem; border-radius: 9999px; background-color: #1A1A1A; color: white; font-size: 0.875rem; font-weight: bold; font-family: 'Inter', sans-serif; }
.toastify { padding: 12px 20px; font-size: 14px; font-weight: 500; border-radius: 8px; }
.hidden { display: none; }
</style>
</head>
<body class="bg-brand-bg font-sans text-brand-text">

<section class="relative h-48 md:h-56 bg-cover bg-center" style="background-image: url('images/1.jpg');">
    <div class="absolute inset-0 bg-black/40"></div>
    <header class="absolute inset-x-0 top-0 z-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-center h-20">
                <a href="/home"><img src="<?=$logo_directory?>" alt="<?=$site_name?> Logo" class="h-10 w-auto filter brightness-0 invert"></a>
            </div>
        </div>
    </header>
    <div class="relative z-10 h-full flex flex-col justify-center items-center text-white text-center px-4">
        <h1 class="text-5xl md:text-6xl font-serif font-semibold pt-12">Checkout</h1>
    </div>
</section>

<main class="container mx-auto px-4 sm:px-6 lg:px-8 py-12 md:py-20">
<div class="grid grid-cols-1 lg:grid-cols-2 lg:gap-x-16 xl:gap-x-24">

<div class="w-full">
<form id="checkout-form" class="space-y-12" onsubmit="return false;">
    <div>
        <div class="step-header"><div class="step-circle">1</div><h2>Contact Information</h2></div>
        <div><label for="email" class="block text-sm font-medium text-brand-gray mb-1">Email Address</label><input type="email" id="email" name="email" class="form-input-sleek" placeholder="you@example.com" required></div>
    </div>
    <div>
        <div class="step-header"><div class="step-circle">2</div><h2>Shipping Address</h2></div>
        <div class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                <div>
                    <label for="full-name" class="block text-sm font-medium text-brand-gray mb-1">Full Name</label><input type="text" id="full-name" name="full-name" class="form-input-sleek" placeholder="Jane Doe" required>
                </div>
                 <div>
                <label for="country" class="block text-sm font-medium text-brand-gray mb-1">Country</label>
                <select id="country" name="country" class="form-input-sleek" required>
                    <option value="">-- Select a Country --</option>
                    <?php foreach ($countriesData as $code => $country): ?>
                        <option value="<?= $code ?>" <?= ($code === 'NG') ? 'selected' : '' ?>>
                            <?= htmlspecialchars($country['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
                <div>
                    <label for="phone-number" class="block text-sm font-medium text-brand-gray mb-1">Phone Number</label>
                    <input type="tel" id="phone-number" name="phone-number" class="form-input-sleek" placeholder="e.g. 08012345678" required>
                    <div id="phone-validation-message" class="mt-1 text-xs text-red-600 hidden"></div>
                    <div id="phone-format-example" class="mt-1 text-xs text-brand-gray hidden"></div>
                </div>
            </div>
            
            <div><label for="address" class="block text-sm font-medium text-brand-gray mb-1">Street Address</label><input type="text" id="address" name="address" class="form-input-sleek" placeholder="123 Main Street" required></div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-6">
                <div><label for="apartment" class="block text-sm font-medium text-brand-gray mb-1">Apartment, Suite, etc. <span class="text-gray-400">(Optional)</span></label><input type="text" id="apartment" name="apartment" class="form-input-sleek" placeholder="Apt 4B, Suite 200"></div>
                            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-x-6 gap-y-6">
                <div><label for="city" class="block text-sm font-medium text-brand-gray mb-1">City</label><input type="text" id="city" name="city" class="form-input-sleek" required></div>
                
                <div>
                    <div id="state-select-wrapper">
                        <label for="state-select" class="block text-sm font-medium text-brand-gray mb-1">State / Province</label>
                        <select id="state-select" name="state" class="form-input-sleek" required>
                            <option value="">-- Select a state --</option>
                        </select>
                    </div>
                    <div id="state-input-wrapper" class="hidden">
                        <label for="state-input" class="block text-sm font-medium text-brand-gray mb-1">State / Province</label>
                        <input type="text" id="state-input" name="state" class="form-input-sleek" required>
                    </div>
                </div>

                <div><label for="postal-code" class="block text-sm font-medium text-brand-gray mb-1">Postal Code / ZIP</label><input type="text" id="postal-code" name="postal-code" class="form-input-sleek" placeholder="12345" required></div>
            </div>

             
            <!-- Shipping Instructions and Delivery Preferences -->
            <div class="space-y-4">
                <h3 class="text-lg font-serif font-semibold text-brand-text">Delivery Preferences</h3>
                
                <div>
                    <label for="delivery-instructions" class="block text-sm font-medium text-brand-gray mb-1">
                        Delivery Instructions <span class="text-gray-400">(Optional)</span>
                    </label>
                    <textarea id="delivery-instructions" name="delivery-instructions" class="form-input-sleek" rows="3" placeholder="e.g., Leave at front desk, Ring doorbell twice, etc."></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <label for="delivery-time-preference" class="block text-sm font-medium text-brand-gray mb-1">
                            Preferred Delivery Time
                        </label>
                        <select id="delivery-time-preference" name="delivery-time-preference" class="form-input-sleek">
                            <option value="">Any time</option>
                            <option value="morning">Morning (9 AM - 12 PM)</option>
                            <option value="afternoon">Afternoon (12 PM - 5 PM)</option>
                            <option value="evening">Evening (5 PM - 8 PM)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
</div>

<div class="w-full mt-16 lg:mt-0">
<div class="bg-white p-8 border border-gray-200/70 lg:sticky lg:top-28">
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-serif font-semibold">Order Summary</h2>
        <div class="flex items-center gap-3">
            <div id="current-currency-indicator" class="text-sm text-brand-gray bg-gray-100 px-3 py-1 rounded-md border border-gray-200">
                <span id="currency-symbol">₦</span> <span id="currency-code">NGN</span>
            </div>
        <button id="currency-switch-btn" class="bg-gray-100 text-sm font-medium px-3 py-1 rounded-md border border-gray-200 hover:bg-gray-200 transition-colors">
            Switch to USD 🇺🇸
        </button>
        </div>
    </div>
    <div id="summary-items-container" class="space-y-5">
        <?php foreach ($cartItems as $item):
            $options = '';
            if (!empty($item['color_name'])) {
                $options .= $item['color_name'];
            } elseif (!empty($item['custom_color_name'])) {
                $options .= 'Custom Color: ' . $item['custom_color_name'];
            }

            if (!empty($item['size_name'])) {
                $options .= ($options ? ' / ' : '') . $item['size_name'];
            } elseif (!empty($item['custom_size_details']) && $item['custom_size_details'] !== '{}') {
                $options .= ($options ? ' / ' : '') . 'Custom Size';
            }
        ?>
        <div class="flex justify-between items-start gap-4" 
             data-name="<?= htmlspecialchars($item['product_name']) ?>" 
             data-quantity="<?= $item['quantity'] ?>" 
             data-color="<?= htmlspecialchars($item['color_name'] ?: '') ?>"
             data-custom-color="<?= htmlspecialchars($item['custom_color_name'] ?: '') ?>"
             data-size="<?= htmlspecialchars($item['size_name'] ?: '') ?>"
             data-custom-size-details="<?= htmlspecialchars($item['custom_size_details'] ?: '{}') ?>"
        >
            <div class="relative flex-shrink-0">
                <img src="/<?= htmlspecialchars($item['product_image']) ?>" alt="<?= htmlspecialchars($item['product_name']) ?>" class="w-20 h-24 object-cover rounded-md border border-gray-200">
                <span class="absolute -top-2 -right-2 bg-brand-gray text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"><?= $item['quantity'] ?></span>
            </div>
            <div class="flex-1">
                <h3 class="font-semibold text-sm"><?= htmlspecialchars($item['product_name']) ?></h3>
                <p class="text-xs text-brand-gray"><?= htmlspecialchars($options) ?></p>
            </div>
            <p class="font-medium text-sm price-display" data-price-ngn="<?= $item['total_price'] ?>">₦<?= number_format($item['total_price'], 2) ?></p>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="mt-6 pt-6 border-t border-gray-200 space-y-4">
        <div class="flex items-center gap-4">
            <input type="text" id="discount-code-input" placeholder="Discount code" class="form-input-sleek flex-1">
            <button type="button" id="apply-discount-btn" class="bg-gray-200/80 text-brand-text text-sm font-semibold px-4 py-2 hover:bg-gray-300 transition-colors rounded-md">Apply</button>
        </div>
        <p id="discount-feedback" class="text-xs h-4"></p>
    </div>
    <div class="mt-6 pt-6 border-t border-gray-200 space-y-3 text-sm">
        <div class="flex justify-between"><span class="text-brand-gray">Subtotal</span><span id="summary-subtotal" class="font-medium price-display" data-price-ngn="<?= $subtotal ?>">₦<?= number_format($subtotal, 2) ?></span></div>
        <div class="flex justify-between"><span class="text-brand-gray">Discount</span><span id="summary-discount" class="font-medium price-display" data-price-ngn="0">- ₦0.00</span></div>
        <div class="flex justify-between"><span class="text-brand-gray">Shipping</span><span id="summary-shipping" class="font-medium price-display" data-price-ngn="0">₦0.00</span></div>
        <div class="flex justify-between text-lg font-semibold pt-4 mt-2 border-t border-gray-200"><span>Total</span><span id="summary-total" class="price-display" data-price-ngn="<?= $subtotal ?>">₦<?= number_format($subtotal, 2) ?></span></div>
    </div>
    <div class="mt-8 pt-6 border-t border-gray-200">
        <h3 class="text-lg font-serif font-semibold mb-4">Payment Options</h3>
        <p class="text-sm text-brand-gray mb-4">Choose your preferred way to finalize the order.</p>
        
        <button type="button" id="paystack-button" class="w-full bg-brand-text text-white py-4 text-center font-semibold hover:bg-gray-800 transition-colors duration-300 flex items-center justify-center gap-3 rounded-md mb-4">
            <i data-feather="lock" class="w-4 h-4"></i><span>Pay with Paystack</span>
        </button>

        <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6 text-sm">
            <div class="flex">
                <div class="flex-shrink-0"><i data-feather="clock" class="h-5 w-5 text-yellow-500"></i></div>
                <div class="ml-3">
                    <p class="font-medium text-yellow-800">Please Note</p>
                    <div class="mt-1 text-yellow-700">
                        <p>Standard processing time is **7-10 working days**.</p>
                        <p>For an express order, please indicate this in your WhatsApp message.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <button type="button" id="whatsapp-button" class="w-full bg-green-600 text-white py-4 text-center font-semibold hover:bg-green-700 transition-colors duration-300 flex items-center justify-center gap-3 rounded-md">
            <i data-feather="message-circle" class="w-4 h-4"></i><span>Order via WhatsApp</span>
        </button>
    </div>
</div>
</div>
</div>
</main>

<footer class="bg-white border-t border-gray-200 mt-16"><div class="container mx-auto px-6 py-8 text-center text-sm text-brand-gray"><p>© <?=date('Y')?> <?=$site_name?>. All Rights Reserved.</p></div></footer>

<script>
console.log('Script loaded');
document.addEventListener('DOMContentLoaded', () => {
    feather.replace();

    // --- DYNAMIC STATE/COUNTRY LOGIC ---
    // NOTE: This array is passed from PHP and contains ALL countries and their states.
    const countriesData = <?= json_encode($countriesData); ?>;
    

    const countrySelect = document.getElementById('country');
    const stateSelectWrapper = document.getElementById('state-select-wrapper');
    const stateSelect = document.getElementById('state-select');
    const stateInputWrapper = document.getElementById('state-input-wrapper');
    const stateInput = document.getElementById('state-input');
    const phoneInput = document.getElementById('phone-number');
    const phoneValidationMessage = document.getElementById('phone-validation-message');
    const phoneFormatExample = document.getElementById('phone-format-example');

    // Function to format phone number with country prefix
    function formatPhoneNumber(phoneNumber, countryCode) {
        const rules = PHONE_VALIDATION_RULES[countryCode];
        if (!rules) return phoneNumber;

        // Remove all non-digit characters except +
        let cleaned = phoneNumber.replace(/[^\d+]/g, '');

        // If it starts with 0, replace with country prefix
        if (cleaned.startsWith('0')) {
            cleaned = rules.prefix + cleaned.substring(1);
        }
        // If it doesn't start with + or country prefix, add country prefix
        else if (!cleaned.startsWith('+')) {
            cleaned = rules.prefix + cleaned;
        }

        return cleaned;
    }

    function updateStateField() {
        const selectedCountryCode = countrySelect.value;
        const country = countriesData[selectedCountryCode];

        stateSelect.innerHTML = '<option value="">-- Select a state --</option>';
        stateInput.value = '';

        if (country && country.states && Object.keys(country.states).length > 0) {
            // Show State/Province Select Dropdown
            stateSelectWrapper.classList.remove('hidden');
            stateInputWrapper.classList.add('hidden');
            stateSelect.disabled = false;
            stateInput.disabled = true;
            stateSelect.required = true;
            stateInput.required = false;

            for (const [stateCode, stateName] of Object.entries(country.states)) {
                const option = document.createElement('option');
                option.value = stateCode;
                option.textContent = stateName;
                stateSelect.appendChild(option);
            }
        } else {
            // Show State/Province Text Input
            stateSelectWrapper.classList.add('hidden');
            stateInputWrapper.classList.remove('hidden');
            stateSelect.disabled = true;
            stateInput.disabled = false;
            stateSelect.required = false;
            stateInput.required = true;
        }
    }

    // Function to update phone field based on country
    function updatePhoneField(countryCode) {
        const rules = PHONE_VALIDATION_RULES[countryCode];
        if (rules) {
            phoneInput.placeholder = rules.placeholder;
            phoneFormatExample.textContent = `Example: ${rules.example}`;
            phoneFormatExample.classList.remove('hidden');
            // Set prefix if not present
            if (!phoneInput.value.startsWith(rules.prefix)) {
                phoneInput.value = rules.prefix;
            }
            // Make prefix non-deletable
            phoneInput.addEventListener('keydown', function(e) {
                const prefixLen = rules.prefix.length;
                if (this.selectionStart <= prefixLen && (e.key === 'Backspace' || e.key === 'Delete' || e.key === 'ArrowLeft')) {
                    e.preventDefault();
                    this.setSelectionRange(prefixLen, prefixLen);
                }
            });
            phoneInput.addEventListener('focus', function() {
                const prefixLen = rules.prefix.length;
                if (!this.value.startsWith(rules.prefix)) {
                    this.value = rules.prefix;
                }
                // Always place cursor after prefix
                setTimeout(() => this.setSelectionRange(this.value.length, this.value.length), 0);
            });
            phoneInput.addEventListener('click', function() {
                const prefixLen = rules.prefix.length;
                if (this.selectionStart < prefixLen) {
                    this.setSelectionRange(prefixLen, prefixLen);
                }
            });
        } else {
            phoneInput.placeholder = 'e.g. +1234567890';
            phoneFormatExample.textContent = 'Example: +1234567890';
            phoneFormatExample.classList.remove('hidden');
            if (!phoneInput.value.startsWith('+')) {
                phoneInput.value = '+';
            }
        }
        
        // Clear validation messages
        phoneValidationMessage.classList.add('hidden');
    }

    // --- CURRENCY LOGIC ---
    const USD_RATE_TO_NGN = <?= USD_EXCHANGE_RATE ?>;
    const COUNTRY_CURRENCY_MAP = <?= $jsCountryCurrencyMap ?>;
    const PHONE_VALIDATION_RULES = <?= $jsPhoneValidationRules ?>;
    let activeCurrency = '<?= $jsCurrentCurrency ?>';
    // Initial rate: 1.0 if base currency is USD, or USD_RATE_TO_NGN if base is NGN (or the rate for any other currency from a cookie)
    let activeExchangeRate = (activeCurrency === 'USD') ? 1.0 : USD_RATE_TO_NGN; 

    const currencySwitchBtn = document.getElementById('currency-switch-btn');

    // Function to fetch USD to target currency rate
    const getExchangeRate = async (currencyCode) => {
        if (currencyCode === 'USD') return 1.0; 
        if (currencyCode === 'NGN') return USD_RATE_TO_NGN; 
        
        try {
            // Use a free, reliable API (Exchangerate.host)
            const apiUrl = `https://api.exchangerate.host/latest?base=USD&symbols=${currencyCode}`;
            const response = await fetch(apiUrl);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.success && data.rates && data.rates[currencyCode]) {
                const rate = parseFloat(data.rates[currencyCode]);
                console.log(`Exchange rate for ${currencyCode}: 1 USD = ${rate} ${currencyCode}`);
                return rate;
            } else {
                throw new Error(`Invalid response format for ${currencyCode}`);
            }
        } catch (e) {
            console.error('Failed to fetch exchange rate for ' + currencyCode, e);
            
            // Fallback rates for common currencies (approximate)
            const fallbackRates = {
                'EUR': 0.85,
                'GBP': 0.73,
                'CAD': 1.25,
                'AUD': 1.35,
                'JPY': 110.0,
                'CHF': 0.92,
                'SEK': 8.5,
                'NOK': 8.7,
                'DKK': 6.3,
                'PLN': 3.9,
                'CZK': 21.5,
                'HUF': 300.0,
                'RUB': 75.0,
                'TRY': 8.5,
                'ZAR': 14.5,
                'BRL': 5.2,
                'MXN': 20.0,
                'INR': 74.0,
                'CNY': 6.4,
                'KRW': 1180.0,
                'SGD': 1.35,
                'HKD': 7.8,
                'THB': 32.0,
                'MYR': 4.2,
                'IDR': 14200.0,
                'PHP': 50.0,
                'VND': 23000.0,
                'AED': 3.67,
                'SAR': 3.75,
                'QAR': 3.64,
                'KWD': 0.30,
                'BHD': 0.38,
                'OMR': 0.38,
                'JOD': 0.71,
                'LBP': 1500.0,
                'EGP': 15.7,
                'MAD': 9.0,
                'TND': 2.8,
                'DZD': 135.0,
                'LYD': 4.5,
                'ETB': 43.0,
                'KES': 108.0,
                'UGX': 3500.0,
                'TZS': 2300.0,
                'ZMW': 18.0,
                'BWP': 11.0,
                'SZL': 14.5,
                'LSL': 14.5,
                'NAD': 14.5,
                'MZN': 64.0,
                'AOA': 650.0,
                'XOF': 550.0,
                'XAF': 550.0,
                'CDF': 2000.0,
                'RWF': 1000.0,
                'BIF': 2000.0,
                'KMF': 420.0,
                'DJF': 178.0,
                'SOS': 580.0,
                'ERN': 15.0,
                'ETB': 43.0,
                'GMD': 52.0,
                'GNF': 10200.0,
                'LRD': 150.0,
                'MGA': 4000.0,
                'MWK': 820.0,
                'MUR': 40.0,
                'SCR': 13.5,
                'SLL': 10200.0,
                'STN': 21.0,
                'SZL': 14.5,
                'TZS': 2300.0,
                'UGX': 3500.0,
                'ZMW': 18.0
            };
            
            if (fallbackRates[currencyCode]) {
                console.log(`Using fallback rate for ${currencyCode}: ${fallbackRates[currencyCode]}`);
                return fallbackRates[currencyCode];
            }
        }
        
        // Final fallback to 1:1 if no specific fallback rate
        console.warn(`No exchange rate available for ${currencyCode}, using 1:1`);
        return 1.0; 
    };

    // Function to format for any currency code
    const formatCurrency = (amount, currency = 'NGN') => {
        try {
            // Use Intl.NumberFormat for proper currency symbol and formatting
            const localeMap = {
                'NGN': 'en-NG',
                'USD': 'en-US',
                'EUR': 'en-EU',
                'GBP': 'en-GB',
                'CAD': 'en-CA',
                'AUD': 'en-AU',
                'JPY': 'ja-JP',
                'CNY': 'zh-CN',
                'INR': 'en-IN',
                'BRL': 'pt-BR',
                'MXN': 'es-MX',
                'RUB': 'ru-RU',
                'KRW': 'ko-KR',
                'SGD': 'en-SG',
                'HKD': 'en-HK',
                'THB': 'th-TH',
                'MYR': 'ms-MY',
                'IDR': 'id-ID',
                'PHP': 'en-PH',
                'VND': 'vi-VN',
                'AED': 'ar-AE',
                'SAR': 'ar-SA',
                'QAR': 'ar-QA',
                'KWD': 'ar-KW',
                'BHD': 'ar-BH',
                'OMR': 'ar-OM',
                'JOD': 'ar-JO',
                'LBP': 'ar-LB',
                'EGP': 'ar-EG',
                'MAD': 'ar-MA',
                'TND': 'ar-TN',
                'DZD': 'ar-DZ',
                'LYD': 'ar-LY',
                'ETB': 'am-ET',
                'KES': 'en-KE',
                'UGX': 'en-UG',
                'TZS': 'en-TZ',
                'ZMW': 'en-ZM',
                'BWP': 'en-BW',
                'SZL': 'en-SZ',
                'LSL': 'en-LS',
                'NAD': 'en-NA',
                'MZN': 'pt-MZ',
                'AOA': 'pt-AO',
                'XOF': 'fr-SN',
                'XAF': 'fr-CM',
                'CDF': 'fr-CD',
                'RWF': 'rw-RW',
                'BIF': 'rn-BI',
                'KMF': 'ar-KM',
                'DJF': 'fr-DJ',
                'SOS': 'so-SO',
                'ERN': 'ti-ER',
                'GMD': 'en-GM',
                'GNF': 'fr-GN',
                'LRD': 'en-LR',
                'MGA': 'mg-MG',
                'MWK': 'en-MW',
                'MUR': 'en-MU',
                'SCR': 'en-SC',
                'SLL': 'en-SL',
                'STN': 'pt-ST'
            };
            
            const locale = localeMap[currency] || 'en-US';
            return new Intl.NumberFormat(locale, { 
                style: 'currency', 
                currency: currency, 
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(amount);
        } catch (e) {
            // Fallback for browsers that don't support the currency code
            const symbolMap = { 
                'NGN': '', 'USD': '$', 'EUR': '', 'GBP': '', 'CAD': 'C$', 'AUD': 'A$',
                'JPY': '', 'CNY': '', 'INR': '', 'BRL': 'R$', 'MXN': '$', 'RUB': '',
                'KRW': '', 'SGD': 'S$', 'HKD': 'HK$', 'THB': '', 'MYR': 'RM', 'IDR': 'Rp',
                'PHP': '', 'VND': '', 'AED': 'د.إ', 'SAR': 'ر.س', 'QAR': 'ر.ق', 'KWD': 'د.ك',
                'BHD': 'د.ب', 'OMR': 'ر.ع.', 'JOD': 'د.أ', 'LBP': 'ل.ل', 'EGP': 'ج.م', 'MAD': 'د.م.',
                'TND': 'د.ت', 'DZD': 'د.ج', 'LYD': 'ل.د', 'ETB': 'ብር', 'KES': 'KSh', 'UGX': 'USh',
                'TZS': 'TSh', 'ZMW': 'ZK', 'BWP': 'P', 'SZL': 'E', 'LSL': 'L', 'NAD': 'N$',
                'MZN': 'MT', 'AOA': 'Kz', 'XOF': 'CFA', 'XAF': 'FCFA', 'CDF': 'FC', 'RWF': 'RF',
                'BIF': 'FBu', 'KMF': 'CF', 'DJF': 'Fdj', 'SOS': 'S', 'ERN': 'Nfk', 'GMD': 'D',
                'GNF': 'FG', 'LRD': 'L$', 'MGA': 'Ar', 'MWK': 'MK', 'MUR': '', 'SCR': '',
                'SLL': 'Le', 'STN': 'Db'
            };
            
            const symbol = symbolMap[currency] || currency + ' ';
            return `${symbol}${amount.toFixed(2)}`;
        }
    };

    function updateAllPrices(targetCurrency) {
        // activeExchangeRate is the value of 1 USD in the target currency (e.g., 1 USD = 0.8 GBP)
        const rate = activeExchangeRate; 
        
        document.querySelectorAll('.price-display').forEach(el => {
            const ngnPrice = parseFloat(el.dataset.priceNgn); // Price stored in NGN
            
            if (!isNaN(ngnPrice)) {
                // 1. Convert NGN Price to USD Price
                const usdPrice = ngnPrice / USD_RATE_TO_NGN; 
                
                // 2. Convert USD Price to Target Currency Price (USD Price * USD_to_Target_Rate)
                let newPrice = usdPrice * rate; 
                
                el.textContent = formatCurrency(newPrice, targetCurrency);
            }
        });
        
        // Update the currency indicator
        const currencySymbol = document.getElementById('currency-symbol');
        const currencyCode = document.getElementById('currency-code');
        
        const symbolMap = { 
            'NGN': '', 'USD': '$', 'EUR': '', 'GBP': '', 'CAD': 'C$', 'AUD': 'A$',
            'JPY': '', 'CNY': '', 'INR': '', 'BRL': 'R$', 'MXN': '$', 'RUB': '',
            'KRW': '', 'SGD': 'S$', 'HKD': 'HK$', 'THB': '', 'MYR': 'RM', 'IDR': 'Rp',
            'PHP': '', 'VND': '', 'AED': 'د.إ', 'SAR': 'ر.س', 'QAR': 'ر.ق', 'KWD': 'د.ك',
            'BHD': 'د.ب', 'OMR': 'ر.ع.', 'JOD': 'د.أ', 'LBP': 'ل.ل', 'EGP': 'ج.م', 'MAD': 'د.م.',
            'TND': 'د.ت', 'DZD': 'د.ج', 'LYD': 'ل.د', 'ETB': 'ብር', 'KES': 'KSh', 'UGX': 'USh',
            'TZS': 'TSh', 'ZMW': 'ZK', 'BWP': 'P', 'SZL': 'E', 'LSL': 'L', 'NAD': 'N$',
            'MZN': 'MT', 'AOA': 'Kz', 'XOF': 'CFA', 'XAF': 'FCFA', 'CDF': 'FC', 'RWF': 'RF',
            'BIF': 'FBu', 'KMF': 'CF', 'DJF': 'Fdj', 'SOS': 'S', 'ERN': 'Nfk', 'GMD': 'D',
            'GNF': 'FG', 'LRD': 'L$', 'MGA': 'Ar', 'MWK': 'MK', 'MUR': '', 'SCR': '',
            'SLL': 'Le', 'STN': 'Db'
        };
        
        if (currencySymbol && currencyCode) {
            currencySymbol.textContent = symbolMap[targetCurrency] || targetCurrency;
            currencyCode.textContent = targetCurrency;
        }
        
        // Update the manual currency toggle button text
        let btnText = '';
        if (targetCurrency === 'NGN') {
             btnText = 'Switch to USD ';
        } else if (targetCurrency === 'USD') {
             btnText = 'Switch to NGN ';
        } else {
             // For any other currency (e.g., EUR), let the button switch back to NGN (the site's base)
             btnText = `Switch to NGN `;
        }
        currencySwitchBtn.textContent = btnText;
    }

    // Country Select Change Listener
    countrySelect.addEventListener('change', async (e) => {
        // 1. Update the state/province fields visibility
        updateStateField(); 

        // 2. Update phone field based on country
        const prevPrefix = PHONE_VALIDATION_RULES[activeCurrency]?.prefix || '';
        const newCountry = e.target.value;
        const newRules = PHONE_VALIDATION_RULES[newCountry];
        if (newRules) {
            const current = phoneInput.value;
            // Remove old prefix if present
            let rest = current;
            if (current.startsWith(prevPrefix)) {
                rest = current.slice(prevPrefix.length);
            } else if (current.startsWith('+')) {
                rest = current.replace(/^\+\d+/, '');
            }
            phoneInput.value = newRules.prefix + rest.replace(/[^\d]/g, '');
        }
        updatePhoneField(newCountry);

        // 3. Determine and switch currency
        const selectedCountryCode = e.target.value;
        const newCurrency = COUNTRY_CURRENCY_MAP[selectedCountryCode] || 'USD'; 

        // Always update currency when country changes, even if it's the same
        // This ensures fresh exchange rates and proper display
        try {
            // Show loading state
            currencySwitchBtn.textContent = 'Loading rates...';
            currencySwitchBtn.disabled = true;
            
            // Fetch the new rate relative to USD
            activeExchangeRate = await getExchangeRate(newCurrency);
            
            // Update the active currency state
            activeCurrency = newCurrency;

            // Update the prices displayed
            updateAllPrices(activeCurrency); 
            
            // Show success feedback
            showToast(`Prices updated to ${newCurrency} for ${countriesData[selectedCountryCode]?.name || 'selected country'}`, 'success');
            
        } catch (error) {
            console.error('Currency update error:', error);
            showToast('Failed to update currency rates. Using default rates.', 'error');
        } finally {
            // Reset button state
            currencySwitchBtn.disabled = false;
        }
    });
    
    // Initial call to set the states and currency based on the default selected country (NG)
    countrySelect.dispatchEvent(new Event('change'));

    // Manual Currency Switch Listener
    currencySwitchBtn.addEventListener('click', async () => {
        let targetCurrency;
        
        if (activeCurrency === 'NGN' || activeCurrency !== 'USD') { 
            // If currently NGN or a foreign currency, switch to USD
            targetCurrency = 'USD';
        } else {
            // If currently USD, switch back to NGN (the site's default base)
            targetCurrency = 'NGN'; 
        }
        
        // Update country dropdown to match the manually selected currency if possible (optional but good UX)
        let countryCodeMatch = Object.keys(COUNTRY_CURRENCY_MAP).find(key => COUNTRY_CURRENCY_MAP[key] === targetCurrency);
        if (countryCodeMatch) {
             // Don't change country to avoid shipping fee side effects, just set the currency.
        }

        // Re-fetch rate 
        activeExchangeRate = await getExchangeRate(targetCurrency);
        activeCurrency = targetCurrency;
        
        updateAllPrices(activeCurrency); 
    });
    
    // Initial check for non-NGN/USD currency set from PHP session/cookie
    if (activeCurrency !== 'NGN' && activeCurrency !== 'USD') {
        getExchangeRate(activeCurrency).then(rate => {
            activeExchangeRate = rate;
            updateAllPrices(activeCurrency);
        });
    } else {
        // Initial call to set the correct currency display on page load
        updateAllPrices(activeCurrency);
    }


    // --- MAIN CHECKOUT LOGIC (modified to use global currency state) ---
    let cartSubtotal = <?= (float)$subtotal ?>;
    let shippingFee = 0;
    let discountAmount = 0;

    const selectors = {
        paystackButton: document.getElementById('paystack-button'),
        whatsappButton: document.getElementById('whatsapp-button'),
        checkoutForm: document.getElementById('checkout-form'),
        discountCodeInput: document.getElementById('discount-code-input'),
        applyDiscountBtn: document.getElementById('apply-discount-btn'),
        discountFeedback: document.getElementById('discount-feedback'),
        summarySubtotal: document.getElementById('summary-subtotal'),
        summaryDiscount: document.getElementById('summary-discount'),
        summaryShipping: document.getElementById('summary-shipping'),
        summaryTotal: document.getElementById('summary-total')
    };
    
    // --- HELPER FUNCTIONS ---
    const showToast = (text, type = "success") => { 
        const background = type === "success" ? "linear-gradient(to right, #00b09b, #96c93d)" : "linear-gradient(to right, #ef4444, #f87171)";
        Toastify({ text, duration: 2500, newWindow: true, close: true, gravity: "top", position: "center", stopOnFocus: true, style: { background, "font-size": "14px" } }).showToast(); 
    };

    const updateOrderSummary = () => {
        const grandTotal = (cartSubtotal - discountAmount) + shippingFee;
        selectors.summarySubtotal.dataset.priceNgn = cartSubtotal;
        selectors.summaryDiscount.dataset.priceNgn = discountAmount;
        selectors.summaryShipping.dataset.priceNgn = shippingFee;
        selectors.summaryTotal.dataset.priceNgn = grandTotal > 0 ? grandTotal : 0;
        
        // Call the general price update function with the currently active currency
        updateAllPrices(activeCurrency);
    };

    // Apply Discount Function
    selectors.applyDiscountBtn.addEventListener('click', async () => {
        const code = selectors.discountCodeInput.value.trim();
        if (!code) {
            selectors.discountFeedback.textContent = '';
            discountAmount = 0;
            updateOrderSummary();
            return;
        }

        selectors.applyDiscountBtn.disabled = true;
        selectors.discountFeedback.textContent = 'Applying...';
        selectors.discountFeedback.classList.remove('text-red-600', 'text-green-600');

        try {
            // The server-side 'apply-discount' endpoint should process the discount based on the NGN subtotal
            const response = await fetch('apply-discount', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ discountCode: code, subtotal: cartSubtotal })
            });
            const result = await response.json();
            
            selectors.discountFeedback.textContent = result.message || '';
            if (result.status === 'success') {
                // The returned discountAmount MUST be in NGN, as all calculations are NGN-based.
                discountAmount = parseFloat(result.discountAmount); 
                selectors.discountFeedback.classList.add('text-green-600');
                selectors.discountFeedback.classList.remove('text-red-600');
                showToast('Discount applied successfully!');
            } else {
                discountAmount = 0;
                selectors.discountFeedback.classList.add('text-red-600');
                selectors.discountFeedback.classList.remove('text-green-600');
            }
            updateOrderSummary();
        } catch (error) {
            console.error('Discount Error:', error);
            showToast('Could not apply discount code.', 'error');
            selectors.discountFeedback.textContent = 'Error applying discount.';
            selectors.discountFeedback.classList.add('text-red-600');
        } finally {
            selectors.applyDiscountBtn.disabled = false;
        }
    });
    
    // --- Paystack Integration ---
    selectors.paystackButton.addEventListener('click', async () => {
        if (!selectors.checkoutForm.checkValidity()) {
            selectors.checkoutForm.reportValidity();
            showToast('Please fill in all required fields.', 'error');
            return;
        }

        // Paystack must be called with an amount in a supported currency (NGN is base).
        const totalInNgn = parseFloat(selectors.summaryTotal.dataset.priceNgn);
        if (totalInNgn <= 0) {
            showToast('The order total must be greater than zero.', 'error');
            return;
        }

        const btn = selectors.paystackButton;
        btn.disabled = true;
        btn.innerHTML = `<i data-feather="loader" class="w-5 h-5 animate-spin"></i> Processing...`;
        feather.replace();

        const selectedCountryCode = countrySelect.value;
        let stateValue;
        if (!stateSelectWrapper.classList.contains('hidden')) {
            stateValue = stateSelect.options[stateSelect.selectedIndex].text;
        } else {
            stateValue = stateInput.value;
        }

        const formData = {
            email: document.getElementById('email').value,
            shippingAddress: {
                fullName: document.getElementById('full-name').value,
                phoneNumber: formatPhoneNumber(document.getElementById('phone-number').value, countrySelect.value),
                address: document.getElementById('address').value,
                apartment: document.getElementById('apartment').value,
                city: document.getElementById('city').value,
                state: stateValue,
                postalCode: document.getElementById('postal-code').value,
                country: countrySelect.options[countrySelect.selectedIndex].text,
                deliveryInstructions: document.getElementById('delivery-instructions').value,
                deliveryTimePreference: document.getElementById('delivery-time-preference').value
            },
            discountCode: selectors.discountCodeInput.value.trim() || null
        };

        try {
            // The server-side 'place-order' should validate and create the order using NGN price
            const response = await fetch('place-order', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            });
            const result = await response.json();

            if (response.ok && result.status === 'success') {
                // Pass the NGN amount to Paystack (amount in kobo/cents)
                let amountInKobo = Math.round(totalInNgn * 100);
                payWithPaystack(result.email, amountInKobo, result.reference, 'NGN');
            } else {
                throw new Error(result.message || 'Failed to place order.');
            }
        } catch (error) {
            console.error('Order Placement Error:', error);
            showToast(error.message, 'error');
            btn.disabled = false;
            btn.innerHTML = `<i data-feather="lock" class="w-4 h-4"></i><span>Pay with Paystack</span>`;
            feather.replace();
        }
    });

    const payWithPaystack = (email, amountKobo, reference, currency) => {
        let handler = PaystackPop.setup({
            key: 'pk_test_0de1d66fdfbb9621ccd971336d399deedfb77ace',
            email: email,
            amount: amountKobo, // amount in kobo/cents
            currency: currency,
            ref: reference,
            callback: function(response) {
                // Redirect to your server for verification
                window.location.href = 'verify-payment?reference=' + response.reference;
            },
            onClose: function() {
                showToast('Payment window closed.', 'error');
                const btn = selectors.paystackButton;
                btn.disabled = false;
                btn.innerHTML = `<i data-feather="lock" class="w-4 h-4"></i><span>Pay with Paystack</span>`;
                feather.replace();
            },
        });
        handler.openIframe();
    };

    // --- WhatsApp Message Generation ---
    selectors.whatsappButton.addEventListener('click', () => {
        const form = document.getElementById('checkout-form');
        if (!form.checkValidity()) {
            form.reportValidity();
            showToast("Please fill out all required fields.", 'error');
            return;
        }

        const fullName = document.getElementById('full-name').value;
        const phone = formatPhoneNumber(document.getElementById('phone-number').value, countrySelect.value);
        const address = document.getElementById('address').value;
        const apartment = document.getElementById('apartment').value;
        const city = document.getElementById('city').value;
        const postalCode = document.getElementById('postal-code').value;
        
        const countryName = countrySelect.options[countrySelect.selectedIndex].text;
        let stateValue;
        if (!stateSelectWrapper.classList.contains('hidden')) {
            stateValue = stateSelect.options[stateSelect.selectedIndex].text;
        } else {
            stateValue = stateInput.value;
        }

        const total = document.getElementById('summary-total').textContent;
        const deliveryInstructions = document.getElementById('delivery-instructions').value;

        let message = `Hello, I want to place an order:\n\n` +
                      `*Name:* ${fullName}\n` +
                      `*Phone:* ${phone}\n`;
        
        let addressLine = address;
        if (apartment) {
            addressLine += `, ${apartment}`;
        }
        addressLine += `, ${city}, ${stateValue} ${postalCode}, ${countryName}`;
        
        message += `*Address:* ${addressLine}\n`;
        
        // if (countrySelect.value === 'NG' && shippingLocationSelect.value) {
        //     message += `*Shipping Location (NG):* ${shippingLocationName}\n`;
        // }
        
        if (deliveryInstructions) {
            message += `*Delivery Instructions:* ${deliveryInstructions}\n`;
        }

        message += `*Grand Total:* ${total}\n` +
                   `*(In ${activeCurrency})*\n\n` +
                   `--- *ORDER DETAILS* ---\n`;
        
        document.querySelectorAll('#summary-items-container > div').forEach(itemElement => {
            const itemData = itemElement.dataset;
            const itemTotalPrice = itemElement.querySelector('.price-display').textContent;
            
            message += `\n*${itemData.name}* (Qty: ${itemData.quantity}) - ${itemTotalPrice}\n`;

            if (itemData.customColor) {
                message += `  - Color: *Custom - ${itemData.customColor}*\n`;
            } else if (itemData.color) {
                message += `  - Color: ${itemData.color}\n`;
            }

            if (itemData.size) {
                message += `  - Option: ${itemData.size}\n`;
            } else if (itemData.customSizeDetails && itemData.customSizeDetails !== '{}') {
                try {
                    const measurements = JSON.parse(itemData.customSizeDetails);
                    message += `  - *Custom Measurements:*\n`;
                    for (const [key, value] of Object.entries(measurements)) {
                        const formattedKey = key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase());
                        if (value) {
                            message += `    - ${formattedKey}: ${value}\n`;
                        }
                    }
                } catch (e) {
                    message += `  - Custom Size Details: ${itemData.customSizeDetails}\n`;
                }
            }
        });

        const whatsappNumber = "+2347030630613";
        const encodedMessage = encodeURIComponent(message);
        window.open(`https://wa.me/${whatsappNumber}?text=${encodedMessage}`, "_blank");
    });
});
</script>
</body>
</html>