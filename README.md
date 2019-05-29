## Configuration
### $wgReverbNotifications
`$wgReverbNotifications` defines the notifications that will be displayed and handled by the extension.  Notification types not defined in this setting may still be returned from the service, but will be display with less formatting information.

Each entry in the array consists of a notification key and a sub-array of parameters.  Notification keys self document their category and subcategory.  For example `user-interest-welcome` is in the `user-interest` subcategory and `user` category.

| Parameter     | Type    | Value(s) | Description                                             |
|---------------|---------|----------|---------------------------------------------------------|
| importantance | integer | 0-9      | The importance of this notification relative to others. |
|               |         |          |                                                         |
|               |         |          |                                                         |


```
$wgReverbNotifications = [
	"user-interest-welcome": [
		"importance": "0"
	],
	"user-interest-talk-page-edit": [
		"importance": "9"
	],
	"user-interest-page-linked": [
		"importance": "1"
	],
	"article-edit-revert": [
		"importance": "8"
	],
	"article-edit-thanks": [
		"importance": "0"
	],
	"user-account-groups-expiration-change": [
		"importance": "9"
	],
	"user-account-groups-changed": [
		"importance": "9"
	]
];
```

`$wgReverbIcons` defines the icons used in SVG format for notifications, subcategories, and categories.  When a notification is displayed it will cascade in order through the notification, subcategory, and category values looking for a matching icon.  For example if an icon is not defined for a notification it will next look in the matching subcategory for an icon.

```
$wgReverbIcons = [
	"category": [
		"user": ".svg",
		"article": ".svg"
	],
	"subcategory": [
		"user-account": ".svg",
		"user-interest": ".svg",
		"article-edit": ".svg"
	],
	"notification": [
		"user-interest-welcome": ".svg",
		"user-interest-talk-page-edit": ".svg",
		"user-interest-page-linked": ".svg",
		"article-edit-revert": ".svg",
		"article-edit-thanks": ".svg",
		"user-account-groups-expiration-change": ".svg",
		"user-account-groups-changed": ".svg"
	]
];
```