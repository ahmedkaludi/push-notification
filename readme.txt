=== Push Notifications for WP - Self Hosted Web Push Notifications ===
Contributors: magazine3  
Requires at least: 3.0  
Tested up to: 6.8 
Requires PHP: 5.6.20 
Stable tag: 1.43  
License: GPLv2 or later  
License URI: http://www.gnu.org/licenses/gpl-2.0.html  
Tags: Push, Web Push, Push Notification, Mobile Notification, Desktop Notifications  

Push Notifications for WP - Self Hosted Web Push Notifications makes it easy to send Web Push notifications to your users for FREE with 2 minutes setup.

== Description ==

Push Notifications for WP - Self Hosted Web Push Notifications makes it easy to send Web Push notifications to your users for FREE with 2 minutes setup. [Documentation](https://pushnotifications.io/docs/)

Send push notifications automatically when a post is published or updated. You can also send custom notifications from the app dashboard. Audience reports and campaign reports are available as well.

== Key Features: ==

* <strong>Automatic Notifications</strong>: Instantly notify your followers whenever you publish a new post.  
* <strong>Custom Notifications</strong>: Use the custom push notification form to send fully personalized notifications — even target specific subscribers or broadcast to all.  
* <strong>Notification Subscription Popup</strong>: Control when and how the subscription prompt appears to your visitors. Fully customize the message, style, and even add custom CSS to match your site’s design.  
* <strong>Scheduled Notifications</strong>: Plan and schedule notifications to be delivered in the future — based on schedule time.  
* <strong>Supported Browsers</strong>: Works seamlessly with Chrome (Desktop & Android), Safari (macOS), Microsoft Edge (Desktop & Android), Opera (Desktop & Android), and Firefox (Desktop & Android) on HTTPS websites.  
* <strong>Push Notification Design</strong>: Choose from four predefined, visually appealing designs. Note: The final appearance may vary depending on the user's device and browser.  
* <strong>Automatic Notification Control</strong>: Choose whether to send notifications for specific post types.  
* <strong>Notification Limit</strong>: Set the maximum number of push notifications that can be sent within a defined time frame.  
* <strong>Notification by User Roles</strong>: Send notifications based on user roles such as Editor, Author, Contributor, Subscriber, and more.  
* <strong>UTM Tracking</strong>: Easily track the performance of your push notifications by adding UTM parameters to your links. Monitor clicks.  
* <strong>Campaigns List</strong>: Easily view sent messages in your dashboard along with their status and click statistics.  
* <strong>Subscribers List</strong>: View the complete list of subscribers, including both active and expired ones.  
* <strong>Visibility</strong>: Control where the push notification subscription popup appears — choose specific pages, posts, categories, tags, and more.  
* <strong>Works with PWAforWP/SuperPWA</strong>: Push notifications work seamlessly with or without a PWA setup.  
* <strong>Global Notification</strong>: Use the global function `pn_send_push_notification_filter` to send push notifications from anywhere.  
* <strong>Shortcode</strong>: Display campaign list on frontend using the `[pn_campaigns]` shortcode.  
* <strong>Compatibility</strong>: Push notifications work seamlessly with most plugins. However, if you want to trigger notifications based on actions from other plugins, specific compatibility may be required. We’ve already implemented compatibility with popular plugins like Polylang, PeepSo, Gravity Forms, BuddyPress/BuddyBoss, and Fluent Community.

== Pro Features: ==

* <strong>Unlimited Notifications</strong>: Send an unlimited number of push notifications to your subscribers without any restrictions or additional costs.
* <strong>Segmentation</strong>: Allow users to subscribe to notifications for specific categories or authors and receive notifications based on their preferences.  
* <strong>Notification to iOS users</strong>: To enable push notifications for iOS users, you need to upgrade to the Pro version.
* <strong>Continuous Development</strong>: We will be working hard to continuously develop this Push Notification solution and release updates constantly so that your forms can continue to work flawlessly.  
* More Push Notification Features coming soon.

👉 <a href="https://pushnotifications.io/pricing" target="_blank"><strong>Upgrade to Pro</strong></a> to unlock all features.

**We Act Fast on Feedback!**  
We are actively developing this plugin and our aim is to make this plugin into the #1 solution for Push Notifications in the world. You can [Request a Feature](https://github.com/ahmedkaludi/push-notification/issues) or [Report a Bug](http://pushnotifications.io/contact/).

**Technical Support**  
Support is provided in the [Forum](https://wordpress.org/support/plugin/push-notification). You can also [Contact us](http://pushnotifications.io/contact). Our turnaround time on email is around 12 hours.

**Would you like to contribute?**  
You may now contribute to this Push Notification plugin on GitHub: [View repository](https://github.com/ahmedkaludi/push-notification)

== Credits ==

Push Notifications for WP uses the following third-party libraries:

1. **Select2** - Select2 is a jQuery based replacement for select boxes.
   - Link: https://github.com/select2/select2
   - License: MIT


== Frequently Asked Questions ==

= How do I install and configure Push Notifications for WP? =  
Once the plugin is activated, navigate to **Push Notification Options** in your WordPress dashboard. Follow the steps to connect your API and configure settings as per your preferences. Full setup takes under 2 minutes.  
📄 Documentation: https://pushnotifications.io/docs/

= Can I send notifications automatically when I publish a post? =  
Yes. Once configured, Push Notifications for WP will automatically send a push notification when you publish or update a post, provided automatic notifications are enabled in the settings.

= How do I send a custom push notification? =  
You can send a custom message at any time via the **Custom Notification** form in the Push Notifications for WP dashboard. Choose the audience, write your message, and send instantly or schedule it for later.

= Is Push Notifications for WP multisite compatible? =  
Yes. Push Notifications for WP allows you to send push notifications across **network sites** in a multisite installation from a single place.

= How do I report bugs or suggest new features for Push Notifications for WP? =  
We love feedback! Please report bugs or suggest new features on our GitHub page:  
https://github.com/ahmedkaludi/push-notification/issues  
Or contact us directly: https://pushnotifications.io/contact

= Will you add a feature I request to Push Notifications for WP? =  
We’re actively improving Push Notifications for WP and prioritize user feedback. You can [submit your feature request on GitHub](https://github.com/Magazine3/Push-Notifications-for-WP/issues) or [contact us directly here](https://pushnotifications.io/contact). We’ll do our best to include your suggestion in a future release.

== Changelog ==

= 1.43 (19 June 2025) =
* Added: Compatibility with Fluent Community #154  
* Fixed: PHP Warning & Notice in push-notification.php: $audience_token_id returns nothing, $response undefined #152  
* Fixed: BuddyBoss / BuddyPress notification not working #158  
* Added: Options to control how many notifications can be sent per hour #145  
* Added: Dedicated option for finer CSS controls #144  
* Added: Feature to show the campaign field with the help of shortcode #160  
* Added: Option "Device Targeting" #95  
* Added: Feature that enables users to choose categories and authors according to their preferences #161  
* Enhancement: After saving API token, browser asks to save as password — which should not happen #105  

= 1.42 (24 April 2025) =
* Enhancement: Tested with WordPress 6.8 #155  
* Fixed: Fatal error when changing the status of a WooCommerce order #153  
* Enhancement: Improvements to Send Notification on selection #146  
* Added: New feature to delete subscribers #143  
* Added: Feature to track country or IP of subscriber #107  

= 1.41 (15 March 2025) =
* Fixed: Post notification not being sent on publish when using Gutenberg editor #148  

= 1.40 (05 February 2025) =
* Added: Integration with BuddyBoss with direct push notifications #132  
* Added: Compatibility with Gravity Forms #140  
* Fixed: Push Notification Disabled checkbox not working on publish/save in Gutenberg Editor #136  
* Fixed: Popup show after 'n' seconds option not working #102  
* Enhancement: woocommerce_order_status_changed | Option to translate status messages #116  
* Added: Custom development #141  

= 1.39 (28 November 2024) =
* Enhancement: Mention self-hosted in marketing #27  
* Enhancement: Reviewed and updated code #119  
* Enhancement: Updated readme with new features list #130  
* Enhancement: Tested with WordPress 6.7 #137  
* Added: Compatibility with Community by PeepSo plugin #138  
* Added: Compatibility with Polylang #135  
* Added: Feature to send notification from one place to all network sites on multisite #134  
* Added: Integration tab to help people integrate with other platforms beyond WordPress #30  

= 1.38 (17 September 2024) =
* Enhancement: Minor improvements #129  
* Added: Feature to resend/reuse campaigns already created #127  

= 1.37 (30 August 2024) =
* Fixed: No active subscriber found when sending push notification #124  

= 1.36 (24 August 2024) =
* Fixed: Conflict with SuperPWA #121  
* Fixed: CSV file not working properly #114  
* Added: Feature to clean logs in the Campaign tab in dashboard #117  
* Enhancement: General improvements #122  
* Enhancement: Tested with WordPress 6.6  

= 1.35 (06 June 2024) =
* Fixed: Install banner — Yes or No option not showing #112  
* Fixed: PHP errors on user end #111  

= 1.33.1 (09 May 2024) =
* Fixed: Issue with plugin activation #103  

Full changelog available at: [changelog.txt](https://plugins.svn.wordpress.org/push-notification/trunk/changelog.txt)