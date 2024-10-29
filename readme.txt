=== Access Defender ===
Contributors: huzaifaalmesbah
Tags: security, access control, ip-detection, proxy
Requires at least: 5.9
Tested up to: 6.6.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Blocks users using VPN or proxy and shows a warning notice using the free ip-api.com API with enhanced IP detection.

== Description ==

Access Defender is a WordPress plugin designed to improve website security by blocking users who are using VPNs or proxies. This plugin uses the **ip-api.com** API to identify if a visitor is accessing your site through a VPN, proxy, or hosting provider. If detected, the user will be presented with a customizable warning message.

=== Features: ===
* Detect and block users who access your site using VPNs, proxies, or hosting providers.
* Customizable warning message and title.
* Simple settings page to enable or disable blocking.

=== Why use Access Defender? ===
VPNs and proxies can sometimes be used to hide malicious intent. By using Access Defender, you can block users with masked IP addresses, ensuring better control over the security of your website.

=== Credits: ===
* **API Provider**: This plugin uses the **ip-api.com** API for VPN and proxy detection. You can learn more about their services and usage at [ip-api.com](https://ip-api.com).
* Special thanks to the team at **ip-api.com** for providing reliable and accurate IP detection services.

=== Third-party API Usage ===
This plugin uses the **ip-api.com** API to detect VPN or proxy usage by checking the visitor's IP address. For more information on ip-api.com’s privacy practices, please see the **Privacy Policy and Terms of Use** below.

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
2. Configure the settings for Access Defender below:
    - **Enable VPN Blocking**: Enable VPN and Proxy Blocking.
    - **Warning Title**: Enter the custom warning title (e.g., "Access Denied").
    - **Warning Message**: Enter the custom warning message to be shown to blocked users.

== Frequently Asked Questions ==

= How does Access Defender work? =
Access Defender uses the ip-api.com API to identify if a visitor is accessing your site through a VPN or proxy by looking up their IP address. If a VPN or proxy is detected, the visitor will be blocked from accessing your website and shown a customizable warning message.

= Is the ip-api.com API free? =
Yes, ip-api.com provides a free tier, which allows a limited number of API requests per minute. You can check their website for more details on pricing and usage limits.

= Can I customize the warning message? =
Yes! You can customize the warning title and message via the plugin's settings page. The message is shown to users blocked by Access Defender.

== Screenshots ==

1. **Settings Page** – Configure the plugin settings, including enabling VPN blocking and setting custom warning messages.
2. **Blocked User Screen** – Example of the custom warning message shown to blocked users.

== Upgrade Notice ==

= 1.0.0 =
* Initial release with VPN and proxy detection using ip-api.com API.
* Added customizable warning messages.
* Improved IP address detection methods.

== Changelog ==

= 1.0.0 =
* Initial release with VPN and proxy detection using ip-api.com API.
* Added customizable warning messages.
* Improved IP address detection methods.

== Privacy and Data Collection ==
This plugin uses a third-party API (ip-api.com) to detect if users are accessing your website through a VPN or proxy. **When a user visits your website, their IP address will be sent to the ip-api.com API** for analysis to determine if it belongs to a VPN or proxy.

=== ip-api.com Privacy Policy and Terms ===
- ip-api.com collects IP addresses solely for the purpose of identifying VPNs, proxies, and hosting providers. They do not collect any personally identifiable information beyond the IP address.
- You can view their Terms and Policies [here](https://ip-api.com/docs/legal).

By using the Access Defender plugin, you agree to the terms of use set forth by the ip-api.com API service. Make sure to review their privacy policies before enabling this plugin.

== License ==
This plugin is licensed under the GPLv2 or later. For more information, please review the [GPLv2 license](https://www.gnu.org/licenses/gpl-2.0.html).

== Terms of Use and Disclaimer ==
Access Defender is provided as-is, without any warranty. While we strive to provide a high level of security, it is important to note that no security measure is 100% foolproof. The plugin uses third-party services, and their availability or changes to their terms could affect the plugin’s functionality.
