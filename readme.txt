=== Access Defender ===
Contributors: huzaifaalmesbah
Tags: security, access control, ip-detection, proxy
Requires at least: 5.9
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Advanced VPN and proxy detection with multiple API providers (free and paid) for enhanced security and reliability.

== Description ==

Access Defender is a comprehensive WordPress plugin designed to improve website security by blocking users who are using VPNs or proxies. With version 1.1.0, the plugin now supports multiple API providers including both free and paid services for enhanced reliability and performance.

=== Features: ===
* **Multiple API Providers**: Choose between free providers with auto-rotation or reliable paid services
* **Free Provider Auto-Rotation**: Automatically switches between free APIs when rate limits are reached
* **Paid Provider Options**: Premium APIs with higher rate limits and better reliability
* **Sequential Provider Management**: Smart rotation system that uses providers in order until limits are hit
* **Real-time Usage Statistics**: Monitor API usage, success rates, and failures in real-time
* **Customizable warning message and title**
* **Enhanced IP detection using multiple HTTP headers**
* **Bypass checks for admin users**
* **Detection and verification of search engine bots**
* **Organized Provider Structure**: Clean separation of free and paid providers

=== Why use Access Defender? ===
VPNs and proxies can sometimes be used to hide malicious intent. By using Access Defender, you can block users with masked IP addresses, ensuring better control over the security of your website.

=== Supported API Providers: ===

**Free Providers:**
* **IP-API.com (Free)**: 45 unique requests per minute - Reliable free service

**Paid Providers:**
* **ProxyCheck.io**: Effective proxy/VPN detection service
* **IPGeolocation.io**: Accurate geolocation and VPN detection service
* **IP-API.com (Pro)**: Coming soon


=== Third-party API Usage ===
This plugin uses multiple third-party APIs to detect VPN or proxy usage by checking the visitor's IP address. The specific API used depends on your configuration (free auto-rotation or selected paid provider). For more information on each provider's privacy practices, please see their respective privacy policies.

== Installation ==

= Plugin Installation Method: =
1. Go to the WordPress dashboard.
2. Navigate to **Plugins > Add New**.
3. Search for "Access Defender".
4. Click on the **Install** button.
5. Activate the plugin after installation.

= Installation via Zip File: =
1. Download the **Access Defender** plugin zip file.
2. Go to **Plugins > Add New > Upload Plugin**.
3. Upload `access-defender.zip` and install it.
4. Activate the plugin.

= Plugin Configuration: =
1. Navigate to **Settings > Access Defender** in your WordPress dashboard.
2. Configure the settings for Access Defender:
    - **Enable VPN Blocking**: Enable VPN and Proxy Blocking.
    - **Provider Configuration**: Choose between free providers (with auto-rotation) or paid provider
    - **Free Providers**: Select multiple free APIs that will rotate automatically when limits are reached
    - **Paid Provider**: Choose a single reliable paid service and enter your API key
    - **Warning Title**: Enter the custom warning title (e.g., "Access Denied").
    - **Warning Message**: Enter the custom warning message to be shown to blocked users.
3. **Monitor Usage**: View real-time statistics showing API usage, success rates, and failures for each provider.

== Frequently Asked Questions ==

= How does Access Defender work? =
Access Defender uses multiple API providers to identify if a visitor is accessing your site through a VPN or proxy by looking up their IP address. You can choose between free providers (with automatic rotation) or paid providers for enhanced reliability. If a VPN or proxy is detected, the visitor will be blocked from accessing your website and shown a customizable warning message.

= What's the difference between free and paid providers? =
Free providers have rate limits (e.g., 45 requests/minute for IP-API.com) but the plugin automatically rotates between multiple free APIs when limits are reached. Paid providers offer higher rate limits, better reliability, and faster response times.

= How does the auto-rotation work for free providers? =
The plugin uses free providers sequentially - it will use the first provider until it hits the rate limit, then automatically switch to the next available provider. This ensures continuous service even with rate limitations.

= Can I use multiple paid providers? =
No, you can select only one paid provider at a time. However, you can easily switch between different paid providers in the settings.

= Can I customize the warning message? =
Yes! You can customize the warning title and message via the plugin's settings page. The message is shown to users blocked by Access Defender.

== Screenshots ==

1. **Settings Page** – Configure provider settings, choose between free auto-rotation or paid providers.
2. **Provider Statistics** – Real-time monitoring of API usage, success rates, and provider status.
3. **Blocked User Screen** – Example of the custom warning message shown to blocked users.

== Changelog ==

= 1.1.0 =
* **Major Update**: Complete multi-provider system implementation
* **New**: Added support for multiple API providers (free and paid)
* **New**: Free provider auto-rotation system with sequential switching
* **New**: Paid provider options with higher rate limits
* **New**: Real-time usage statistics and monitoring
* **New**: Dynamic API key management for paid providers
* **New**: Organized provider structure (Free/Paid subdirectories)
* **Added**: IP-API.com Pro (paid) service with 1,000 requests/minute
* **Added**: IPGeolocation.io paid provider
* **Added**: ProxyCheck.io as a paid provider
* **Added**: API key validation via link
* **Added**: Smart rate limiting with per-minute and monthly tracking
* **Added**: Enhanced admin interface with dynamic provider selection
* **Added**: Better error handling and fallback systems
* **Fixed**: Plugin options structure and migration system

= 1.0.4 =
* Fixed: Corrected file path in uninstall.php to prevent fatal error during plugin uninstallation
* Fixed: Updated require_once path from 'src/Core/ActivationHooks.php' to 'includes/Core/ActivationHooks.php'

= 1.0.3 =
* Improved dashboard UI for better user experience
* Enhanced settings page layout and styling
* Added responsive design elements to admin interface

== Upgrade Notice ==

= 1.1.0 =
* Major update with multiple API providers, auto-rotation, and enhanced reliability. Existing settings will be automatically migrated.

= 1.0.4 =
* Fixed critical uninstall error - update recommended for proper plugin removal

= 1.0.2 =
* Improved dashboard UI for better user experience
* Enhanced settings page layout and styling
* Added responsive design elements to admin interface

= 1.0.1 =
* Updated to improve compatibility with the latest WordPress version.
* Minor bug fixes and performance improvements.
* Enhanced IP detection using multiple HTTP headers.
* Bypass checks for admin users.
* Detection and verification of search engine bots.

= 1.0.0 =
* Initial release with VPN and proxy detection using ip-api.com API.
* Added customizable warning messages.
* Improved IP address detection methods.

== Privacy and Data Collection ==
This plugin uses third-party APIs to detect if users are accessing your website through a VPN or proxy. **When a user visits your website, their IP address will be sent to the selected API provider(s)** for analysis to determine if it belongs to a VPN or proxy.

=== Data Sharing with API Providers ===
Depending on your configuration, IP addresses may be shared with:
- **IP-API.com**: For both free and paid tiers - [Privacy Policy](https://ip-api.com/docs/legal)
- **ProxyCheck.io**: For paid service - [Privacy Policy](https://proxycheck.io/)
- **IPGeolocation.io**: For paid service - [Privacy Policy](https://ipgeolocation.io/privacy-policy)

Each provider collects IP addresses solely for the purpose of identifying VPNs, proxies, and hosting providers. They do not collect personally identifiable information beyond the IP address.

By using the Access Defender plugin, you agree to the terms of use set forth by the API service(s) you have configured. Make sure to review their privacy policies before enabling this plugin.

=== Appsero SDK and Analytics ===
Access Defender uses [Appsero](https://appsero.com) SDK to collect some telemetry data upon user's confirmation. This helps us to troubleshoot problems faster & make product improvements.

Appsero SDK **does not gather any data by default.** The SDK only starts gathering basic telemetry data **when a user allows it via the admin notice**. We collect the data to ensure a great user experience for all our users. 

Integrating Appsero SDK **DOES NOT IMMEDIATELY** start gathering data, **without confirmation from users in any case.**

Learn more about how [Appsero collects and uses this data](https://appsero.com/privacy-policy/).

== License ==
This plugin is licensed under the GPLv2 or later. For more information, please review the [GPLv2 license](https://www.gnu.org/licenses/gpl-2.0.html).

== Terms of Use and Disclaimer ==
Access Defender is provided as-is, without any warranty. While we strive to provide a high level of security, it is important to note that no security measure is 100% foolproof. The plugin uses third-party services, and their availability or changes to their terms could affect the plugin's functionality.
