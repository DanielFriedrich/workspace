<?php
if (!defined('SP_APP')) { exit; }

/** Linien-Icons (24 × 24, stroke = currentColor). */
function icon_paths()
{
    return array(
        // Kategorien
        'spot'    => '<path d="M3.5 11 12.6 5.6l3 5.2-9.1 5.3z"/><path d="M12.6 5.6l3 5.2"/><path d="M18 6.2l3-1.6M18.6 9.6h3M17.8 13l2.8 1.6"/><path d="M8.5 15.4V21M5.5 21h6"/>',
        'effect'  => '<path d="M12 2v3"/><circle cx="12" cy="13" r="7.5"/><path d="M4.5 13h15M12 5.5v15M6.4 8.2c3.6 1.9 7.6 1.9 11.2 0M6.4 17.8c3.6-1.9 7.6-1.9 11.2 0"/>',
        'laser'   => '<rect x="2" y="15" width="8" height="6" rx="1.2"/><path d="M10 16.5 21 4.5M10 17.5l11.5-7M10 18.5h11.5"/>',
        'fog'     => '<path d="M3 8.5h10.5a2.75 2.75 0 1 0-2.6-3.6"/><path d="M3 12.5h15a3 3 0 1 1-2.8 4"/><path d="M3 16.5h7.5M3 20.5h12"/>',
        'flame'   => '<path d="M12 2.5c.6 3.6 5.5 5.6 5.5 11a5.5 5.5 0 0 1-11 0c0-2.8 1.4-4.6 2.7-5.8.2 2.1 1.1 3.3 2.4 3.6-.6-2.9-.4-5.6.4-8.8z"/><path d="M12 21.5c-1.7 0-2.8-1.2-2.8-2.8 0-1.6 1.4-2.5 2.2-3.7.5 1.3 2.4 1.9 2.4 3.9 0 1.5-.8 2.6-1.8 2.6z"/>',
        'spark'   => '<path d="M12 2v5M12 17v5M2 12h5M17 12h5M4.9 4.9l3.5 3.5M15.6 15.6l3.5 3.5M4.9 19.1l3.5-3.5M15.6 8.4l3.5-3.5"/><circle cx="12" cy="12" r="1.6"/>',
        'speaker' => '<rect x="5" y="2" width="14" height="20" rx="2.5"/><circle cx="12" cy="14.5" r="4"/><circle cx="12" cy="14.5" r="1.2"/><circle cx="12" cy="6.5" r="1.6"/>',
        'mixer'   => '<rect x="3" y="3" width="18" height="18" rx="2.5"/><path d="M8 7v10M12 7v10M16 7v10"/><path d="M6.5 10.5h3M10.5 14h3M14.5 9h3"/>',
        'mic'     => '<rect x="9" y="2.5" width="6" height="11" rx="3"/><path d="M5.5 10.5a6.5 6.5 0 0 0 13 0M12 17v4.5M8.5 21.5h7"/>',
        'cable'   => '<path d="M7 2.5v5M11 2.5v5"/><path d="M5 7.5h8v3a4 4 0 0 1-8 0z"/><path d="M9 14.5v1.5a5 5 0 0 0 5 5h1a4 4 0 0 0 4-4V3"/>',
        'truss'   => '<path d="M5 3v18M11 3v18M5 3h6M5 21h6M5 6l6 3-6 3 6 3-6 3"/><path d="M15 21h6M18 21V9M15 9h6"/>',
        'box'     => '<path d="M21 8 12 3 3 8v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
        // UI
        'search'  => '<circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/>',
        'bag'     => '<path d="M5 8h14l-1.2 12.1a1.5 1.5 0 0 1-1.5 1.4H7.7a1.5 1.5 0 0 1-1.5-1.4z"/><path d="M9 10V6a3 3 0 0 1 6 0v4"/>',
        'calendar'=> '<rect x="3" y="4.5" width="18" height="16.5" rx="2.5"/><path d="M3 9.5h18M8 2.5v4M16 2.5v4"/>',
        'pin'     => '<path d="M12 21.5s-7-6.2-7-11.5a7 7 0 0 1 14 0c0 5.3-7 11.5-7 11.5z"/><circle cx="12" cy="10" r="2.5"/>',
        'sliders' => '<path d="M4 6h10M18 6h2M4 12h4M12 12h8M4 18h12M20 18h0"/><circle cx="16" cy="6" r="2"/><circle cx="10" cy="12" r="2"/><circle cx="18" cy="18" r="2"/>',
        'x'       => '<path d="M6 6l12 12M18 6 6 18"/>',
        'plus'    => '<path d="M12 5v14M5 12h14"/>',
        'minus'   => '<path d="M5 12h14"/>',
        'check'   => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'arrow-r' => '<path d="M5 12h14M13 6l6 6-6 6"/>',
        'arrow-l' => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
        'chev-l'  => '<path d="m15 6-6 6 6 6"/>',
        'chev-r'  => '<path d="m9 6 6 6-6 6"/>',
        'chev-d'  => '<path d="m6 9 6 6 6-6"/>',
        'user'    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
        'users'   => '<circle cx="9" cy="8" r="3.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0"/><path d="M15.5 4.6a3.5 3.5 0 0 1 0 6.8M17.5 14a6.5 6.5 0 0 1 4 6"/>',
        'menu'    => '<path d="M4 7h16M4 12h16M4 17h16"/>',
        'clock'   => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
        'trash'   => '<path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>',
        'edit'    => '<path d="M4 20h4L19 9l-4-4L4 16z"/><path d="m13.5 6.5 4 4"/>',
        'copy'    => '<rect x="8" y="8" width="13" height="13" rx="2"/><path d="M16 8V5a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h3"/>',
        'lock'    => '<rect x="4.5" y="10.5" width="15" height="10.5" rx="2"/><path d="M8 10.5V7a4 4 0 0 1 8 0v3.5"/>',
        'logout'  => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3M10 17l5-5-5-5M15 12H3"/>',
        'grid'    => '<rect x="3" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="3" width="7.5" height="7.5" rx="1.5"/><rect x="3" y="13.5" width="7.5" height="7.5" rx="1.5"/><rect x="13.5" y="13.5" width="7.5" height="7.5" rx="1.5"/>',
        'list'    => '<path d="M9 6h11M9 12h11M9 18h11"/><circle cx="4.5" cy="6" r="1"/><circle cx="4.5" cy="12" r="1"/><circle cx="4.5" cy="18" r="1"/>',
        'tag'     => '<path d="M3 12V4a1 1 0 0 1 1-1h8l9 9-9 9z"/><circle cx="7.5" cy="7.5" r="1.5"/>',
        'ban'     => '<circle cx="12" cy="12" r="9"/><path d="m5.6 5.6 12.8 12.8"/>',
        'timeline'=> '<path d="M3 5h18M3 19h18"/><rect x="5" y="8.5" width="8" height="2.5" rx="1"/><rect x="9" y="13" width="10" height="2.5" rx="1"/>',
        'settings'=> '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.8l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.8-.3 1.7 1.7 0 0 0-1 1.5V21a2 2 0 1 1-4 0v-.1a1.7 1.7 0 0 0-1.1-1.5 1.7 1.7 0 0 0-1.8.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.8 1.7 1.7 0 0 0-1.5-1H3a2 2 0 1 1 0-4h.1a1.7 1.7 0 0 0 1.5-1.1 1.7 1.7 0 0 0-.3-1.8l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.8.3H9a1.7 1.7 0 0 0 1-1.5V3a2 2 0 1 1 4 0v.1a1.7 1.7 0 0 0 1 1.5 1.7 1.7 0 0 0 1.8-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.8V9a1.7 1.7 0 0 0 1.5 1H21a2 2 0 1 1 0 4h-.1a1.7 1.7 0 0 0-1.5 1z"/>',
        'eye'     => '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/>',
        'info'    => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5v.5"/>',
        'alert'   => '<path d="M12 3 2 20.5h20z"/><path d="M12 10v5M12 17.5v.5"/>',
        'truck'   => '<path d="M2 6h12v10H2zM14 10h4l3 3v3h-7"/><circle cx="6" cy="18" r="2"/><circle cx="17" cy="18" r="2"/>',
        'phone'   => '<path d="M5 3h4l2 5-2.5 1.5a11 11 0 0 0 6 6L16 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 5a2 2 0 0 1 2-2z"/>',
        'mail'    => '<rect x="3" y="5" width="18" height="14" rx="2"/><path d="m3 7 9 6 9-6"/>',
        'upload'  => '<path d="M12 16V4M7 9l5-5 5 5M4 16v3a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-3"/>',
        'external'=> '<path d="M14 4h6v6M20 4l-9 9M18 14v5a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1h5"/>',
        'return'  => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10.5a5.5 5.5 0 0 1 0 11H11"/>',
        'handover'=> '<path d="M3 13h4l3 3h6a2 2 0 0 0 0-4h-4"/><path d="M7 17l2 2h9l3-4"/><path d="M12 3v6M9 6l3 3 3-3"/>',
        'sparkle' => '<path d="M12 3c.5 4.5 2.5 6.5 7 7-4.5.5-6.5 2.5-7 7-.5-4.5-2.5-6.5-7-7 4.5-.5 6.5-2.5 7-7z"/><path d="M19 15c.2 1.6.9 2.3 2.5 2.5-1.6.2-2.3.9-2.5 2.5-.2-1.6-.9-2.3-2.5-2.5 1.6-.2 2.3-.9 2.5-2.5z"/>',
    );
}

function category_icon_options()
{
    return array(
        'spot' => 'Scheinwerfer', 'effect' => 'Effekt / Spiegelkugel', 'laser' => 'Laser', 'fog' => 'Nebel',
        'flame' => 'Flamme', 'spark' => 'Funken', 'speaker' => 'Lautsprecher', 'mixer' => 'Mischpult',
        'mic' => 'Mikrofon', 'cable' => 'Kabel / Strom', 'truss' => 'Stativ / Traverse', 'box' => 'Sonstiges',
    );
}

/** SVG-Sprite einmal pro Seite ausgeben. */
function icon_sprite()
{
    $out = '<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">';
    foreach (icon_paths() as $name => $paths) {
        $out .= '<symbol id="i-' . $name . '" viewBox="0 0 24 24">' . $paths . '</symbol>';
    }
    return $out . '</svg>';
}

function icon($name, $class = '')
{
    $paths = icon_paths();
    if (!isset($paths[$name])) {
        $name = 'box';
    }
    return '<svg class="icon' . ($class ? ' ' . e($class) : '') . '" aria-hidden="true" focusable="false"><use href="#i-' . e($name) . '"></use></svg>';
}
