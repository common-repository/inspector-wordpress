
=== InspectorWordpress ===
Contributors: Andreas Schipplock
Tags: security
Requires at least: 2.5.0
Tested up to: 2.5.0
Stable tag: 1.3

Prevent possible attacks on your wordpress blog.

== Description ==

This plugin monitors each request to your wordpress blog and based on conditions you can define in the
options pane it interrupts the attacker's action and logs it.

Under "Dashboard"->"Inspector Wordpress" you can see all attempts.
Under "Manage"->"Logs" you can delete all logged attempts.
Under "Options"->"Inspector Wordpress" you can edit the conditions and behaviour of "InspectorWordpress".

If you want to discuss InspectorWordpress send your concerns to: 

	inspector-wordpress@googlegroups.com

== Installation ==

1. Unzip the archive in the "/wp-content/plugins/" directory.
2. the plugin directory must be named "inspector-wordpress". The plugin expects the "InspectorWordpress.php" in "/wp-content/plugins/inspector-wordpress/".
3. Activate the plugin through the "Plugins" menu in Wordpress.
4. You can configure the InspectorWordpress plugin with the `Options > InspectorWordpress` page of your adminpanel.

== Changelog ==
* 1.3 : wordpress 2.5.0 compatibility
* 1.2 : fixed a bug with php4 due to php documentation mistake and some naming fault in the setup procedure that caused the mysql-table not being created when the directory name was not "InspectorWordpress". The plugin now expects the files in "inspector-wordpress".
* 1.1 : Compatibility with wp2.2.2
* 1.0 : Initial version
