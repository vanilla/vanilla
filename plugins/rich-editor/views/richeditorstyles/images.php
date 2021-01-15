<?php

use Vanilla\Formatting\Formats\RichFormat;

$imageeOperations = '[
	{
		"insert": "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididun ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\nUltrices gravida dictum fusce ut placerat orci. Ultrices gravida dictum fusce ut placerat orci nulla. Felis donec et odio pellentesque diam volutpat commodo sed. Dictum non consectetur a erat nam at lectus urna. Eros donec ac odio tempor orci dapibus ultrices in. Senectus et netus et malesuada fames. Penatibus et magnis dis parturient. Turpis egestas sed tempus urna et pharetra pharetra massa. Cum sociis natoque penatibus et magnis dis. A arcu cursus vitae congue mauris rhoncus aenean. Porttitor rhoncus dolor purus non enim praesent elementum facilisis leo. Nunc sed augue lacus viverra. Euismod in pellentesque massa placerat duis ultricies lacus sed. Erat nam at lectus urna duis. Id velit ut tortor pretium viverra suspendisse potenti nullam. Pellentesque sit amet porttitor eget dolor. Quam pellentesque nec nam aliquam sem et tortor consequat. Quam vulputate dignissim suspendisse in est ante in. Quam viverra orci sagittis eu volutpat odio facilisis.\n"
	},
	{ "attributes": { "header": { "level": 2, "ref": "" } }, "insert": "\n" },
	{ "insert": "Horizontal Image 4:3" },
	{ "attributes": { "header": { "level": 2, "ref": "" } }, "insert": "\n" },
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 1452,
					"height": 968,
                    "embedType": "image"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
				}
			}
		}
	},
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 1452,
					"height": 968,
					"embedType": "image",
					"displaySize": "medium",
					"float": "left"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
				}
			}
		}
	},
	{
		"insert": "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididun ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n"
	},
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 1452,
					"height": 968,
					"embedType": "image",
					"displaySize": "small",
					"float": "left"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
				}
			}
		}
	},
	{
		"insert": "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididun ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n"
	},
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 1452,
					"height": 968,
					"embedType": "image",
					"displaySize": "medium",
					"float": "right"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
				}
			}
		}
	},
	{
		"insert": "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididun ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n"
	},
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 1452,
					"height": 968,
					"embedType": "image",
					"displaySize": "small",
					"float": "right"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
				}
			}
		}
	},
	{
		"insert": "Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididun ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris.\nDuis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.\n"
	},
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 1452,
					"height": 968,
					"embedType": "image",
					"displaySize": "medium"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
				}
			}
		}
	},
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 1452,
					"height": 968,
					"embedType": "image",
					"displaySize": "small"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=1950&q=80"
				}
			}
		}
	},
	{ "attributes": { "header": { "level": 2, "ref": "" } }, "insert": "\n" },
	{ "insert": "Image size smaller than container" },
	{ "attributes": { "header": { "level": 2, "ref": "" } }, "insert": "\n" },
	{ "insert": "This container should be full-width but the image inside is only 200px wide." },
	{
		"insert": {
			"embed-external": {
				"data": {
					"url": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=200&q=80",
					"name": "Untitled Image",
					"type": "image/jpeg",
					"size": 0,
					"width": 200,
					"height": 133,
					"embedType": "image"
				},
				"loaderData": {
					"type": "link",
					"link": "https://images.unsplash.com/photo-1605488966261-8e25a31b5a4b?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=crop&w=200&q=80"
				}
			}
		}
	},
	{ "insert": "\n" }
]';

echo "<div class='Item-Body'><div class='Message userContent'>";
echo "<h2>Images</h2>";
echo Gdn::formatService()->renderHTML($imageeOperations, RichFormat::FORMAT_KEY);
echo "</div>";

