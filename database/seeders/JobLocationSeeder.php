<?php
// database/seeders/JobLocationSeeder.php

namespace Database\Seeders;

use App\Models\Job\JobLocation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class JobLocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $locations = [
            // ===== CENTRAL REGION =====
            [
                'country' => 'UG',
                'district' => 'Kampala',
                'slug' => 'kampala-jobs-uganda',
                'description' => 'Capital city of Uganda. Find jobs in Kampala across all sectors including finance, technology, retail, hospitality, and government.',
                'meta_title' => 'Jobs in Kampala, Uganda - Find Employment in the Capital City',
                'meta_description' => 'Browse thousands of jobs in Kampala, Uganda. Find employment opportunities in banking, IT, hospitality, education, healthcare, and government sectors.',
                'is_active' => true,
                'sort_order' => 1
            ],
            [
                'country' => 'UG',
                'district' => 'Wakiso',
                'slug' => 'wakiso-jobs-uganda',
                'description' => 'Most populous district surrounding Kampala. Includes major towns: Entebbe, Nansana, Kira, Makindye. Home to Entebbe International Airport.',
                'meta_title' => 'Jobs in Wakiso District, Uganda - Entebbe, Nansana, Kira',
                'meta_description' => 'Find jobs in Wakiso district including Entebbe, Nansana, and Kira. Opportunities in aviation, tourism, manufacturing, and education.',
                'is_active' => true,
                'sort_order' => 2
            ],
            [
                'country' => 'UG',
                'district' => 'Mukono',
                'slug' => 'mukono-jobs-uganda',
                'description' => 'Central business district with growing industrial and educational sectors. Home to Uganda Christian University and several manufacturing industries.',
                'meta_title' => 'Jobs in Mukono, Uganda - Industrial and Educational Opportunities',
                'meta_description' => 'Browse jobs in Mukono district. Find employment in education, manufacturing, agriculture, and service industries.',
                'is_active' => true,
                'sort_order' => 3
            ],
            [
                'country' => 'UG',
                'district' => 'Masaka',
                'slug' => 'masaka-jobs-uganda',
                'description' => 'Major commercial center in Central region. Key industries: agriculture, trade, education, and healthcare.',
                'meta_title' => 'Jobs in Masaka, Uganda - Commercial Hub Opportunities',
                'meta_description' => 'Find jobs in Masaka district. Opportunities in agriculture, trade, education, healthcare, and public service.',
                'is_active' => true,
                'sort_order' => 4
            ],
            [
                'country' => 'UG',
                'district' => 'Mpigi',
                'slug' => 'mpigi-jobs-uganda',
                'description' => 'District with rich cultural heritage. Key sectors: agriculture, tourism (Mpambire drum makers), and small-scale manufacturing.',
                'meta_title' => 'Jobs in Mpigi, Uganda - Cultural & Agricultural Opportunities',
                'meta_description' => 'Browse employment opportunities in Mpigi district. Find jobs in agriculture, tourism, crafts, and local government.',
                'is_active' => true,
                'sort_order' => 5
            ],
            [
                'country' => 'UG',
                'district' => 'Kayunga',
                'slug' => 'kayunga-jobs-uganda',
                'description' => 'Agricultural district known for rice and banana farming. Opportunities in agribusiness and trade.',
                'meta_title' => 'Jobs in Kayunga, Uganda - Agricultural Opportunities',
                'meta_description' => 'Find jobs in Kayunga district. Opportunities in rice farming, banana plantations, agribusiness, and trade.',
                'is_active' => true,
                'sort_order' => 6
            ],
            [
                'country' => 'UG',
                'district' => 'Luwero',
                'slug' => 'luwero-jobs-uganda',
                'description' => 'Historically significant district. Growing agricultural and industrial sectors. Part of the Luwero-Rwenzori Development Programme area [citation:5].',
                'meta_title' => 'Jobs in Luwero, Uganda - Development Zone Opportunities',
                'meta_description' => 'Browse jobs in Luwero district. Find employment in agriculture, manufacturing, education, and development programs.',
                'is_active' => true,
                'sort_order' => 7
            ],
            [
                'country' => 'UG',
                'district' => 'Mityana',
                'slug' => 'mityana-jobs-uganda',
                'description' => 'District with growing commercial activities. Key sectors: agriculture, trade, and small-scale industries.',
                'meta_title' => 'Jobs in Mityana, Uganda - Commercial & Agricultural',
                'meta_description' => 'Find jobs in Mityana district. Opportunities in agriculture, trade, education, and local enterprises.',
                'is_active' => true,
                'sort_order' => 8
            ],
            [
                'country' => 'UG',
                'district' => 'Kalungu',
                'slug' => 'kalungu-jobs-uganda',
                'description' => 'District with agricultural focus. Known for coffee and banana farming.',
                'meta_title' => 'Jobs in Kalungu, Uganda - Agricultural Opportunities',
                'meta_description' => 'Browse employment in Kalungu district. Find jobs in coffee farming, banana plantations, and local trade.',
                'is_active' => true,
                'sort_order' => 9
            ],
            [
                'country' => 'UG',
                'district' => 'Kyotera',
                'slug' => 'kyotera-jobs-uganda',
                'description' => 'New district created in 2017 [citation:1]. Agricultural hub near the Tanzanian border. Key sector: maize and banana farming.',
                'meta_title' => 'Jobs in Kyotera, Uganda - Border District Opportunities',
                'meta_description' => 'Find jobs in Kyotera district. Opportunities in cross-border trade, agriculture, and local services.',
                'is_active' => true,
                'sort_order' => 10
            ],
            [
                'country' => 'UG',
                'district' => 'Kasanda',
                'slug' => 'kasanda-jobs-uganda',
                'description' => 'New district created in 2018 from Mubende [citation:1]. Agricultural and trading center.',
                'meta_title' => 'Jobs in Kasanda, Uganda - Agricultural Hub',
                'meta_description' => 'Browse jobs in Kasanda district. Find employment in agriculture, trade, and local government services.',
                'is_active' => true,
                'sort_order' => 11
            ],
            [
                'country' => 'UG',
                'district' => 'Gomba',
                'slug' => 'gomba-jobs-uganda',
                'description' => 'District with expanding agricultural sector. Known for livestock and crop farming.',
                'meta_title' => 'Jobs in Gomba, Uganda - Livestock & Agriculture',
                'meta_description' => 'Find jobs in Gomba district. Opportunities in livestock farming, crop agriculture, and rural development.',
                'is_active' => true,
                'sort_order' => 12
            ],
            [
                'country' => 'UG',
                'district' => 'Bukomansimbi',
                'slug' => 'bukomansimbi-jobs-uganda',
                'description' => 'Agricultural district focused on coffee and banana production.',
                'meta_title' => 'Jobs in Bukomansimbi, Uganda - Coffee & Banana Farming',
                'meta_description' => 'Browse employment in Bukomansimbi district. Find jobs in coffee farming, banana plantations, and local trade.',
                'is_active' => true,
                'sort_order' => 13
            ],
            [
                'country' => 'UG',
                'district' => 'Butambala',
                'slug' => 'butambala-jobs-uganda',
                'description' => 'District with agricultural and fishing activities along Lake Victoria.',
                'meta_title' => 'Jobs in Butambala, Uganda - Fishing & Agriculture',
                'meta_description' => 'Find jobs in Butambala district. Opportunities in fishing, agriculture, and local services.',
                'is_active' => true,
                'sort_order' => 14
            ],
            [
                'country' => 'UG',
                'district' => 'Sembabule',
                'slug' => 'sembabule-jobs-uganda',
                'description' => 'District known for livestock farming. Growing opportunities in animal husbandry and trade.',
                'meta_title' => 'Jobs in Sembabule, Uganda - Livestock & Ranching',
                'meta_description' => 'Browse jobs in Sembabule district. Find employment in livestock farming, ranching, and agricultural trade.',
                'is_active' => true,
                'sort_order' => 15
            ],
            [
                'country' => 'UG',
                'district' => 'Lyantonde',
                'slug' => 'lyantonde-jobs-uganda',
                'description' => 'Small district along the Masaka-Mbarara highway. Key sector: trade and livestock.',
                'meta_title' => 'Jobs in Lyantonde, Uganda - Trade & Livestock Hub',
                'meta_description' => 'Find jobs in Lyantonde district. Opportunities in trade, livestock marketing, and transportation.',
                'is_active' => true,
                'sort_order' => 16
            ],
            [
                'country' => 'UG',
                'district' => 'Rakai',
                'slug' => 'rakai-jobs-uganda',
                'description' => 'District on the Tanzanian border. Key sectors: cross-border trade, fishing, and agriculture. Includes Kasensero fishing village [citation:9].',
                'meta_title' => 'Jobs in Rakai, Uganda - Border Trade & Fishing',
                'meta_description' => 'Browse jobs in Rakai district. Find opportunities in cross-border trade, fishing, and agriculture.',
                'is_active' => true,
                'sort_order' => 17
            ],
            
            // ===== WESTERN REGION =====
            [
                'country' => 'UG',
                'district' => 'Mbarara',
                'slug' => 'mbarara-jobs-uganda',
                'description' => 'Commercial hub of Western Uganda. Major sectors: banking, education, healthcare, trade, and livestock. Designated as one of Uganda\'s 15 cities [citation:8].',
                'meta_title' => 'Jobs in Mbarara, Uganda - Western Commercial Hub',
                'meta_description' => 'Find jobs in Mbarara city and district. Opportunities in banking, education, healthcare, trade, and livestock marketing.',
                'is_active' => true,
                'sort_order' => 18
            ],
            [
                'country' => 'UG',
                'district' => 'Kasese',
                'slug' => 'kasese-jobs-uganda',
                'description' => 'District at the foothills of Rwenzori Mountains. Key sectors: tourism, mining, agriculture. Home to Queen Elizabeth National Park.',
                'meta_title' => 'Jobs in Kasese, Uganda - Tourism & Mining Hub',
                'meta_description' => 'Browse jobs in Kasese district. Find employment in tourism, mining, agriculture, and hospitality near Rwenzori Mountains.',
                'is_active' => true,
                'sort_order' => 19
            ],
            [
                'country' => 'UG',
                'district' => 'Kabale',
                'slug' => 'kabale-jobs-uganda',
                'description' => 'Highland district known for terraced farming. Key sectors: education, agriculture, tourism. One of the proposed cities [citation:8].',
                'meta_title' => 'Jobs in Kabale, Uganda - Highland Agriculture & Education',
                'meta_description' => 'Find jobs in Kabale district. Opportunities in terraced farming, education, tourism, and public service.',
                'is_active' => true,
                'sort_order' => 20
            ],
            [
                'country' => 'UG',
                'district' => 'Fort Portal',
                'slug' => 'fort-portal-jobs-uganda',
                'description' => 'Tourism city in Western Uganda. Key sectors: tourism, education, tea production. Designated city status in 2020 [citation:8].',
                'meta_title' => 'Jobs in Fort Portal, Uganda - Tourism City Opportunities',
                'meta_description' => 'Browse jobs in Fort Portal city. Find employment in tourism, hospitality, education, and tea processing.',
                'is_active' => true,
                'sort_order' => 21
            ],
            [
                'country' => 'UG',
                'district' => 'Hoima',
                'slug' => 'hoima-jobs-uganda',
                'description' => 'Oil and gas hub of Uganda. Key sectors: petroleum, agriculture, trade. Designated city status in 2020 [citation:8]. Includes Kitoba Town Council [citation:6].',
                'meta_title' => 'Jobs in Hoima, Uganda - Oil & Gas Industry',
                'meta_description' => 'Find jobs in Hoima city and district. Opportunities in oil and gas, petroleum services, agriculture, and construction.',
                'is_active' => true,
                'sort_order' => 22
            ],
            [
                'country' => 'UG',
                'district' => 'Masindi',
                'slug' => 'masindi-jobs-uganda',
                'description' => 'District with diverse economy. Key sectors: tourism (Murchison Falls), agriculture, trade.',
                'meta_title' => 'Jobs in Masindi, Uganda - Tourism & Agriculture',
                'meta_description' => 'Browse jobs in Masindi district. Find employment in tourism, hospitality, agriculture, and trade near Murchison Falls.',
                'is_active' => true,
                'sort_order' => 23
            ],
            [
                'country' => 'UG',
                'district' => 'Bushenyi',
                'slug' => 'bushenyi-jobs-uganda',
                'description' => 'Agricultural district known for tea and banana farming. Growing education sector.',
                'meta_title' => 'Jobs in Bushenyi, Uganda - Tea & Agriculture',
                'meta_description' => 'Find jobs in Bushenyi district. Opportunities in tea plantations, banana farming, education, and trade.',
                'is_active' => true,
                'sort_order' => 24
            ],
            [
                'country' => 'UG',
                'district' => 'Rukungiri',
                'slug' => 'rukungiri-jobs-uganda',
                'description' => 'District with agricultural economy. Known for coffee and banana farming.',
                'meta_title' => 'Jobs in Rukungiri, Uganda - Coffee & Agriculture',
                'meta_description' => 'Browse jobs in Rukungiri district. Find employment in coffee farming, agriculture, education, and local trade.',
                'is_active' => true,
                'sort_order' => 25
            ],
            [
                'country' => 'UG',
                'district' => 'Kanungu',
                'slug' => 'kanungu-jobs-uganda',
                'description' => 'District known for tourism (Bwindi Impenetrable Forest) and agriculture. Home to gorilla trekking.',
                'meta_title' => 'Jobs in Kanungu, Uganda - Gorilla Tourism & Agriculture',
                'meta_description' => 'Find jobs in Kanungu district. Opportunities in tourism, hospitality, conservation, and agriculture near Bwindi.',
                'is_active' => true,
                'sort_order' => 26
            ],
            [
                'country' => 'UG',
                'district' => 'Kisoro',
                'slug' => 'kisoro-jobs-uganda',
                'description' => 'Mountainous district with stunning scenery. Key sectors: tourism, agriculture, cross-border trade with Rwanda and DRC.',
                'meta_title' => 'Jobs in Kisoro, Uganda - Mountain Tourism & Cross-border Trade',
                'meta_description' => 'Browse jobs in Kisoro district. Find employment in tourism, hospitality, cross-border trade, and agriculture.',
                'is_active' => true,
                'sort_order' => 27
            ],
            [
                'country' => 'UG',
                'district' => 'Kagadi',
                'slug' => 'kagadi-jobs-uganda',
                'description' => 'New district created in 2016 from Kibaale [citation:1]. Agricultural and trading center.',
                'meta_title' => 'Jobs in Kagadi, Uganda - New District Opportunities',
                'meta_description' => 'Find jobs in Kagadi district. Opportunities in agriculture, trade, education, and public service in this new district.',
                'is_active' => true,
                'sort_order' => 28
            ],
            [
                'country' => 'UG',
                'district' => 'Kakumiro',
                'slug' => 'kakumiro-jobs-uganda',
                'description' => 'New district created in 2016 from Kibaale [citation:1]. Agricultural focus with growing opportunities.',
                'meta_title' => 'Jobs in Kakumiro, Uganda - Agricultural Development',
                'meta_description' => 'Browse jobs in Kakumiro district. Find employment in agriculture, local trade, and community development.',
                'is_active' => true,
                'sort_order' => 29
            ],
            [
                'country' => 'UG',
                'district' => 'Rubanda',
                'slug' => 'rubanda-jobs-uganda',
                'description' => 'New district created in 2016 from Kabale [citation:1]. Highland agriculture and education.',
                'meta_title' => 'Jobs in Rubanda, Uganda - Highland Opportunities',
                'meta_description' => 'Find jobs in Rubanda district. Opportunities in highland agriculture, education, and local services.',
                'is_active' => true,
                'sort_order' => 30
            ],
            [
                'country' => 'UG',
                'district' => 'Bunyangabu',
                'slug' => 'bunyangabu-jobs-uganda',
                'description' => 'New district created in 2017 from Kabarole [citation:1]. Agricultural and trading center.',
                'meta_title' => 'Jobs in Bunyangabu, Uganda - Emerging Opportunities',
                'meta_description' => 'Browse jobs in Bunyangabu district. Find employment in agriculture, trade, and community development.',
                'is_active' => true,
                'sort_order' => 31
            ],
            [
                'country' => 'UG',
                'district' => 'Kikuube',
                'slug' => 'kikuube-jobs-uganda',
                'description' => 'New district created in 2018 from Hoima [citation:1]. Oil and gas related opportunities.',
                'meta_title' => 'Jobs in Kikuube, Uganda - Oil & Gas Region',
                'meta_description' => 'Find jobs in Kikuube district. Opportunities in oil and gas services, agriculture, and construction.',
                'is_active' => true,
                'sort_order' => 32
            ],
            [
                'country' => 'UG',
                'district' => 'Kazo',
                'slug' => 'kazo-jobs-uganda',
                'description' => 'New district created in 2019 from Kiruhura [citation:1]. Livestock and cattle keeping focus.',
                'meta_title' => 'Jobs in Kazo, Uganda - Livestock & Ranching',
                'meta_description' => 'Browse jobs in Kazo district. Find employment in livestock farming, ranching, and agricultural trade.',
                'is_active' => true,
                'sort_order' => 33
            ],
            [
                'country' => 'UG',
                'district' => 'Rwampara',
                'slug' => 'rwampara-jobs-uganda',
                'description' => 'New district created in 2019 from Mbarara [citation:1]. Agricultural and trading center.',
                'meta_title' => 'Jobs in Rwampara, Uganda - Agricultural Hub',
                'meta_description' => 'Find jobs in Rwampara district. Opportunities in agriculture, trade, and local services.',
                'is_active' => true,
                'sort_order' => 34
            ],
            [
                'country' => 'UG',
                'district' => 'Kitagwenda',
                'slug' => 'kitagwenda-jobs-uganda',
                'description' => 'New district created in 2019 from Kamwenge [citation:1]. Emerging opportunities in agriculture.',
                'meta_title' => 'Jobs in Kitagwenda, Uganda - Emerging District',
                'meta_description' => 'Browse jobs in Kitagwenda district. Find employment in agriculture, fishing, and community development.',
                'is_active' => true,
                'sort_order' => 35
            ],
            
            // ===== EASTERN REGION =====
            [
                'country' => 'UG',
                'district' => 'Jinja',
                'slug' => 'jinja-jobs-uganda',
                'description' => 'Industrial hub of Uganda. Source of the Nile. Key sectors: manufacturing, hydroelectric power, tourism, education. Designated city status in 2020 [citation:8].',
                'meta_title' => 'Jobs in Jinja, Uganda - Industrial & Tourism Hub',
                'meta_description' => 'Find jobs in Jinja city and district. Opportunities in manufacturing, hydroelectric power, tourism, education, and adventure sports.',
                'is_active' => true,
                'sort_order' => 36
            ],
            [
                'country' => 'UG',
                'district' => 'Mbale',
                'slug' => 'mbale-jobs-uganda',
                'description' => 'Commercial center of Eastern Uganda. Key sectors: trade, education, healthcare, agriculture. Designated city status in 2020 [citation:8]. Includes Mbale Town Council [citation:6].',
                'meta_title' => 'Jobs in Mbale, Uganda - Eastern Commercial Hub',
                'meta_description' => 'Browse jobs in Mbale city and district. Find employment in trade, education, healthcare, agriculture, and manufacturing.',
                'is_active' => true,
                'sort_order' => 37
            ],
            [
                'country' => 'UG',
                'district' => 'Tororo',
                'slug' => 'tororo-jobs-uganda',
                'description' => 'Industrial and mining district. Key sectors: cement manufacturing, agriculture, trade. Recently divided into Mukuju, Mulanda, and Kisoko districts [citation:4].',
                'meta_title' => 'Jobs in Tororo, Uganda - Industrial & Mining',
                'meta_description' => 'Find jobs in Tororo district. Opportunities in cement manufacturing, mining, agriculture, and trade. Includes new districts of Mukuju, Mulanda, and Kisoko.',
                'is_active' => true,
                'sort_order' => 38
            ],
            [
                'country' => 'UG',
                'district' => 'Soroti',
                'slug' => 'soroti-jobs-uganda',
                'description' => 'Commercial center of Teso sub-region. Key sectors: agriculture, trade, education. Designated city status in 2020 [citation:8]. Includes Katine Town Council [citation:6].',
                'meta_title' => 'Jobs in Soroti, Uganda - Teso Regional Hub',
                'meta_description' => 'Browse jobs in Soroti city and district. Find employment in agriculture, trade, education, and public service.',
                'is_active' => true,
                'sort_order' => 39
            ],
            [
                'country' => 'UG',
                'district' => 'Busia',
                'slug' => 'busia-jobs-uganda',
                'description' => 'Border district with Kenya. Key sectors: cross-border trade, customs, clearing and forwarding, agriculture.',
                'meta_title' => 'Jobs in Busia, Uganda - Kenya Border Trade',
                'meta_description' => 'Find jobs in Busia district. Opportunities in cross-border trade, clearing and forwarding, customs, and agriculture.',
                'is_active' => true,
                'sort_order' => 40
            ],
            [
                'country' => 'UG',
                'district' => 'Malaba',
                'slug' => 'malaba-jobs-uganda',
                'description' => 'Major border point with Kenya. Key sectors: clearing and forwarding, logistics, trade, customs.',
                'meta_title' => 'Jobs in Malaba, Uganda - Logistics & Border Trade',
                'meta_description' => 'Browse jobs in Malaba border point. Find employment in clearing and forwarding, logistics, customs, and cross-border trade.',
                'is_active' => true,
                'sort_order' => 41
            ],
            [
                'country' => 'UG',
                'district' => 'Gulu',
                'slug' => 'gulu-jobs-uganda',
                'description' => 'Commercial center of Northern Uganda. Key sectors: trade, education, NGO sector, agriculture. Designated city status in 2020 [citation:8]. Includes Awach Town Council [citation:6].',
                'meta_title' => 'Jobs in Gulu, Uganda - Northern Commercial Hub',
                'meta_description' => 'Find jobs in Gulu city and district. Opportunities in trade, education, NGO sector, agriculture, and development programs.',
                'is_active' => true,
                'sort_order' => 42
            ],
            [
                'country' => 'UG',
                'district' => 'Lira',
                'slug' => 'lira-jobs-uganda',
                'description' => 'Commercial center of Lango sub-region. Key sectors: trade, education, agriculture. Designated city status in 2020 [citation:8]. Includes Ogur Town Council [citation:6].',
                'meta_title' => 'Jobs in Lira, Uganda - Lango Regional Hub',
                'meta_description' => 'Browse jobs in Lira city and district. Find employment in trade, education, agriculture, and public service.',
                'is_active' => true,
                'sort_order' => 43
            ],
            [
                'country' => 'UG',
                'district' => 'Arua',
                'slug' => 'arua-jobs-uganda',
                'description' => 'Commercial center of West Nile region. Key sectors: trade, education, healthcare, cross-border trade with DRC. Designated city status in 2020 [citation:8]. Includes Vurra Town Council [citation:6].',
                'meta_title' => 'Jobs in Arua, Uganda - West Nile Commercial Hub',
                'meta_description' => 'Find jobs in Arua city and district. Opportunities in trade, education, healthcare, and cross-border trade with DRC.',
                'is_active' => true,
                'sort_order' => 44
            ],
            [
                'country' => 'UG',
                'district' => 'Kitgum',
                'slug' => 'kitgum-jobs-uganda',
                'description' => 'District in Acholi sub-region. Key sectors: agriculture, trade, NGO sector.',
                'meta_title' => 'Jobs in Kitgum, Uganda - Acholi Region Opportunities',
                'meta_description' => 'Browse jobs in Kitgum district. Find employment in agriculture, trade, NGO sector, and community development.',
                'is_active' => true,
                'sort_order' => 45
            ],
            [
                'country' => 'UG',
                'district' => 'Pader',
                'slug' => 'pader-jobs-uganda',
                'description' => 'District in Acholi sub-region. Key sectors: agriculture, education, development programs.',
                'meta_title' => 'Jobs in Pader, Uganda - Agriculture & Development',
                'meta_description' => 'Find jobs in Pader district. Opportunities in agriculture, education, NGO sector, and rural development.',
                'is_active' => true,
                'sort_order' => 46
            ],
            [
                'country' => 'UG',
                'district' => 'Moroto',
                'slug' => 'moroto-jobs-uganda',
                'description' => 'Commercial center of Karamoja region. Key sectors: mining, livestock, trade, NGO sector. Proposed city status [citation:8].',
                'meta_title' => 'Jobs in Moroto, Uganda - Karamoja Regional Hub',
                'meta_description' => 'Browse jobs in Moroto district. Find employment in mining, livestock, trade, and development programs in Karamoja.',
                'is_active' => true,
                'sort_order' => 47
            ],
            [
                'country' => 'UG',
                'district' => 'Kotido',
                'slug' => 'kotido-jobs-uganda',
                'description' => 'District in Karamoja region. Key sectors: livestock, agriculture, NGO sector.',
                'meta_title' => 'Jobs in Kotido, Uganda - Livestock & Development',
                'meta_description' => 'Find jobs in Kotido district. Opportunities in livestock keeping, agriculture, and community development programs.',
                'is_active' => true,
                'sort_order' => 48
            ],
            [
                'country' => 'UG',
                'district' => 'Nebbi',
                'slug' => 'nebbi-jobs-uganda',
                'description' => 'District in West Nile region. Key sectors: trade, fishing, agriculture.',
                'meta_title' => 'Jobs in Nebbi, Uganda - West Nile Opportunities',
                'meta_description' => 'Browse jobs in Nebbi district. Find employment in trade, fishing, agriculture, and local services.',
                'is_active' => true,
                'sort_order' => 49
            ],
            [
                'country' => 'UG',
                'district' => 'Yumbe',
                'slug' => 'yumbe-jobs-uganda',
                'description' => 'District in West Nile region. Home to refugee settlements. Key sectors: humanitarian work, agriculture, trade.',
                'meta_title' => 'Jobs in Yumbe, Uganda - Refugee Response & Agriculture',
                'meta_description' => 'Find jobs in Yumbe district. Opportunities in humanitarian work, NGO sector, agriculture, and cross-border trade.',
                'is_active' => true,
                'sort_order' => 50
            ],
            [
                'country' => 'UG',
                'district' => 'Omoro',
                'slug' => 'omoro-jobs-uganda',
                'description' => 'New district created in 2016 from Gulu [citation:1]. Agricultural and developing area.',
                'meta_title' => 'Jobs in Omoro, Uganda - New District Opportunities',
                'meta_description' => 'Browse jobs in Omoro district. Find employment in agriculture, education, and community development in this new district.',
                'is_active' => true,
                'sort_order' => 51
            ],
            [
                'country' => 'UG',
                'district' => 'Nabilatuk',
                'slug' => 'nabilatuk-jobs-uganda',
                'description' => 'New district created in 2018 from Nakapiripirit [citation:1]. Emerging opportunities in Karamoja.',
                'meta_title' => 'Jobs in Nabilatuk, Uganda - Emerging Karamoja District',
                'meta_description' => 'Find jobs in Nabilatuk district. Opportunities in livestock, agriculture, and development programs.',
                'is_active' => true,
                'sort_order' => 52
            ],
            [
                'country' => 'UG',
                'district' => 'Obongi',
                'slug' => 'obongi-jobs-uganda',
                'description' => 'New district created in 2019 from Moyo [citation:1]. Includes Obongi Central Town Council [citation:9]. Border area with South Sudan.',
                'meta_title' => 'Jobs in Obongi, Uganda - Border District',
                'meta_description' => 'Browse jobs in Obongi district. Find employment in cross-border trade, agriculture, and community services.',
                'is_active' => true,
                'sort_order' => 53
            ],
            [
                'country' => 'UG',
                'district' => 'Madi Okollo',
                'slug' => 'madi-okollo-jobs-uganda',
                'description' => 'New district created in 2019 from Arua [citation:1]. Emerging opportunities in West Nile.',
                'meta_title' => 'Jobs in Madi Okollo, Uganda - New West Nile District',
                'meta_description' => 'Find jobs in Madi Okollo district. Opportunities in agriculture, trade, and community development.',
                'is_active' => true,
                'sort_order' => 54
            ],
            [
                'country' => 'UG',
                'district' => 'Karenga',
                'slug' => 'karenga-jobs-uganda',
                'description' => 'New district created in 2019 from Kaabong [citation:1]. Tourism potential near Kidepo Valley National Park.',
                'meta_title' => 'Jobs in Karenga, Uganda - Tourism & Wildlife',
                'meta_description' => 'Browse jobs in Karenga district. Find employment in tourism, conservation, livestock, and community development near Kidepo Valley.',
                'is_active' => true,
                'sort_order' => 55
            ],
            [
                'country' => 'UG',
                'district' => 'Kalaki',
                'slug' => 'kalaki-jobs-uganda',
                'description' => 'New district created in 2019 from Kaberamaido [citation:1]. Agricultural focus.',
                'meta_title' => 'Jobs in Kalaki, Uganda - Agricultural District',
                'meta_description' => 'Find jobs in Kalaki district. Opportunities in agriculture, fishing, and local trade.',
                'is_active' => true,
                'sort_order' => 56
            ],
            [
                'country' => 'UG',
                'district' => 'Bugweri',
                'slug' => 'bugweri-jobs-uganda',
                'description' => 'New district created in 2018 from Iganga [citation:1]. Emerging opportunities in Busoga.',
                'meta_title' => 'Jobs in Bugweri, Uganda - New Busoga District',
                'meta_description' => 'Browse jobs in Bugweri district. Find employment in agriculture, trade, and community services.',
                'is_active' => true,
                'sort_order' => 57
            ],
            [
                'country' => 'UG',
                'district' => 'Namisindwa',
                'slug' => 'namisindwa-jobs-uganda',
                'description' => 'New district created in 2017 from Mbale [citation:1]. Agricultural focus on the slopes of Mt. Elgon.',
                'meta_title' => 'Jobs in Namisindwa, Uganda - Mt. Elgon Agriculture',
                'meta_description' => 'Find jobs in Namisindwa district. Opportunities in coffee farming, agriculture, and trade on Mt. Elgon slopes.',
                'is_active' => true,
                'sort_order' => 58
            ],
            [
                'country' => 'UG',
                'district' => 'Butebo',
                'slug' => 'butebo-jobs-uganda',
                'description' => 'New district created in 2017 from Pallisa [citation:1]. Includes Kabwangasi Town Council [citation:9]. Agricultural focus.',
                'meta_title' => 'Jobs in Butebo, Uganda - New Eastern District',
                'meta_description' => 'Browse jobs in Butebo district. Find employment in agriculture, trade, and community development.',
                'is_active' => true,
                'sort_order' => 59
            ],
            [
                'country' => 'UG',
                'district' => 'Pakwach',
                'slug' => 'pakwach-jobs-uganda',
                'description' => 'New district created in 2017 from Nebbi [citation:1]. Bridge over Nile River. Tourism and fishing potential.',
                'meta_title' => 'Jobs in Pakwach, Uganda - Nile Bridge Gateway',
                'meta_description' => 'Find jobs in Pakwach district. Opportunities in fishing, tourism, transportation, and trade at the Nile crossing.',
                'is_active' => true,
                'sort_order' => 60
            ],
        ];

        foreach ($locations as $location) {
            JobLocation::firstOrCreate(
                ['slug' => $location['slug']],
                $location
            );
        }

        $this->command->info(count($locations) . ' job locations seeded successfully!');
    }
}