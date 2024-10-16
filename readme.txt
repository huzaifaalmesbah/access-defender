=== AccessGuard VPN Blocker ===
Contributors: huzaifaalmesbah
Tags: security, vpn blocker, proxy blocker, access control
Requires at least: 5.0
Tested up to: 6.4
Stable tag: 1.5
Requires PHP: 7.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Block access to your WordPress site for users connecting through VPNs or proxies.

== Description ==

AccessGuard VPN Blocker enhances your WordPress site's security by preventing access from VPN and proxy connections. It uses the free IPapi.co service to detect VPN and proxy usage, providing an additional layer of protection for your website.

Key Features:
* Automatically detects and blocks VPN and proxy connections
* Uses multiple HTTP headers for accurate client IP detection
* Displays a customizable warning message to blocked users
* Lightweight and easy to use with no configuration required

Please note: This plugin uses the free tier of IPapi.co for VPN and proxy detection. Be sure to review their terms of service and ensure your usage complies with their policies.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/accessguard-vpn-blocker` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. The plugin will start working immediately with default settings.

== Frequently Asked Questions ==

= How does this plugin detect VPNs and proxies? =

AccessGuard VPN Blocker uses the IPapi.co service to check if the visitor's IP address is associated with a VPN or proxy service.

= Will this plugin slow down my website? =

The plugin makes an API call for each visitor, which may add a small amount of load time. However, for most websites, this should not cause noticeable slowdowns.

= Can legitimate users be blocked accidentally? =

While we strive for accuracy, there's always a small chance that some legitimate users might be blocked, especially if they're using a hosting service that's flagged as a proxy. If this becomes an issue, you may need to implement a whitelist feature.

= Is this plugin GDPR compliant? =

The plugin itself doesn't store any personal data. However, it does send IP addresses to IPapi.co for checking. Please review IPapi.co's privacy policy to ensure compliance with your privacy requirements.

== Changelog ==

= 1.5 =
* Initial release

== Upgrade Notice ==

= 1.5 =
Initial release of AccessGuard VPN Blocker.

== API Terms and Policies ==

This plugin uses the free tier of IPapi.co for VPN and proxy detection. By using this plugin, you agree to comply with IPapi.co's terms of service and policies. Please review their terms at https://ip-api.com/docs/legal.

Key points to note:
1. The free tier is limited to 45 requests per minute.
2. Commercial use requires a paid plan.
3. Caching of results is recommended to minimize API calls.
4. IP-API.com collects and stores IP addresses for service improvement and abuse prevention.

It is your responsibility to ensure that your use of this plugin and the IPapi.co service complies with all applicable laws and regulations, including data protection laws like GDPR.

For full details, please visit: https://ip-api.com/docs/legal
