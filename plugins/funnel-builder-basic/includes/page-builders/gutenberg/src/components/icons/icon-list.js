export const IconList = (iconSize = 24) => {
	return [
		{
			id: 'paragraph',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M18.3 4H9.9v-.1l-.9.2c-2.3.4-4 2.4-4 4.8s1.7 4.4 4 4.8l.7.1V20h1.5V5.5h2.9V20h1.5V5.5h2.7V4z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'formatBold',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M14.7 11.3c1-.6 1.5-1.6 1.5-3 0-2.3-1.3-3.4-4-3.4H7v14h5.8c1.4 0 2.5-.3 3.3-1 .8-.7 1.2-1.7 1.2-2.9.1-1.9-.8-3.1-2.6-3.7zm-5.1-4h2.3c.6 0 1.1.1 1.4.4.3.3.5.7.5 1.2s-.2 1-.5 1.2c-.3.3-.8.4-1.4.4H9.6V7.3zm4.6 9c-.4.3-1 .4-1.7.4H9.6v-3.9h2.9c.7 0 1.3.2 1.7.5.4.3.6.8.6 1.5s-.2 1.2-.6 1.5z"></path>
				</svg>
			),
			type: 'admin-menu',
		},
		{
			id: 'formatItalic',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M12.5 5L10 19h1.9l2.5-14z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'link',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M15.6 7.2H14v1.5h1.6c2 0 3.7 1.7 3.7 3.7s-1.7 3.7-3.7 3.7H14v1.5h1.6c2.8 0 5.2-2.3 5.2-5.2 0-2.9-2.3-5.2-5.2-5.2zM4.7 12.4c0-2 1.7-3.7 3.7-3.7H10V7.2H8.4c-2.9 0-5.2 2.3-5.2 5.2 0 2.9 2.3 5.2 5.2 5.2H10v-1.5H8.4c-2 0-3.7-1.7-3.7-3.7zm4.6.9h5.3v-1.5H9.3v1.5z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'alignLeft',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M4 19.8h8.9v-1.5H4v1.5zm8.9-15.6H4v1.5h8.9V4.2zm-8.9 7v1.5h16v-1.5H4z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'alignRight',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M11.1 19.8H20v-1.5h-8.9v1.5zm0-15.6v1.5H20V4.2h-8.9zM4 12.8h16v-1.5H4v1.5z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'alignJustify',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="https://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M4 12.8h16v-1.5H4v1.5zm0 7h12.4v-1.5H4v1.5zM4 4.3v1.5h16V4.3H4z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'alignCenter',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M16.4 4.2H7.6v1.5h8.9V4.2zM4 11.2v1.5h16v-1.5H4zm3.6 8.6h8.9v-1.5H7.6v1.5z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'addSubmenu',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M16.4 4.2H7.6v1.5h8.9V4.2zM4 11.2v1.5h16v-1.5H4zm3.6 8.6h8.9v-1.5H7.6v1.5z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'arrowDown',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="-2 -2 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M9 2h2v12l4-4 2 1-7 7-7-7 2-1 4 4V2z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'arrowUp',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="-2 -2 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M11 18H9V6l-4 4-2-1 7-7 7 7-2 1-4-4v12z"></path>
				</svg>
			),
			type: 'all',
		},
		{
			id: 'arrowLeft',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="-2 -2 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M11 18H9V6l-4 4-2-1 7-7 7 7-2 1-4-4v12z"></path>
				</svg>
			),
			type: 'social',
		},
		{
			id: 'arrowRight',
			icon: (
				<svg
					width={iconSize}
					height={iconSize}
					xmlns="http://www.w3.org/2000/svg"
					viewBox="-2 -2 24 24"
					role="img"
					aria-hidden="true"
					focusable="false"
				>
					<path d="M11 18H9V6l-4 4-2-1 7-7 7 7-2 1-4-4v12z"></path>
				</svg>
			),
			type: 'all',
		},
	];
};
