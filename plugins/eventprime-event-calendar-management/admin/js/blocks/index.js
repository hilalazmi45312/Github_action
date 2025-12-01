(function (wp) {
  // Destructure from wp and keep everything scoped inside this IIFE
  const { registerBlockType } = wp.blocks;
  const { createElement, useState, useEffect } = wp.element;
  const { __: ep__ } = wp.i18n; // alias to avoid global "__"
  const { InspectorControls } = wp.blockEditor || wp.editor; // compatibility
  const { TextControl, SelectControl, ServerSideRender, PanelBody } = wp.components;
  const el = createElement;

  // Reusable icon
  const iconEl = el(
    'svg',
    { width: 20, height: 20 },
    el('rect', { fill: 'none', height: '24', width: '24' }),
    el('rect', { height: '4', width: '4', x: '10', y: '4' }),
    el('rect', { height: '4', width: '4', x: '4', y: '16' }),
    el('rect', { height: '4', width: '4', x: '4', y: '10' }),
    el('rect', { height: '4', width: '4', x: '4', y: '4' }),
    el('rect', { height: '4', width: '4', x: '16', y: '4' }),
    el('polygon', { points: '11,17.86 11,20 13.1,20 19.08,14.03 16.96,11.91' }),
    el('polygon', { points: '14,12.03 14,10 10,10 10,14 12.03,14' }),
    el('path', { d: 'M20.85,11.56l-1.41-1.41c-0.2-0.2-0.51-0.2-0.71,0l-1.06,1.06l2.12,2.12l1.06-1.06C21.05,12.07,21.05,11.76,20.85,11.56z' })
  );

  // --- Small in-file cache for events to reuse across block edits in the same session
  let __epEventsCache = null;
  const fetchEvents = async () => {
    if (__epEventsCache) return __epEventsCache;
    try {
      const result = await wp.apiFetch({ path: 'eventprime/v1/events' });
      // Ensure options format: [{ label, value }] for SelectControl
      __epEventsCache = Array.isArray(result)
        ? result.map((e) => ({
            label: e.title || e.name || `#${e.id}`,
            value: String(e.id),
          }))
        : [];
    } catch (e) {
      __epEventsCache = [];
    }
    return __epEventsCache;
  };

  // ---------------------
  // Event Calendar Block
  // ---------------------
  registerBlockType('eventprime-blocks/event-calendar', {
    title: ep__('EventPrime Event Calendar'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    edit() {
      return el('div', {}, [el(ServerSideRender, { block: 'eventprime-blocks/event-calendar' })]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Event Countdown Block
  // ---------------------
  registerBlockType('eventprime-blocks/event-countdown', {
    title: ep__('EventPrime Event Countdown'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      eid: {
        type: 'string',
        default: '0', // keep a static default; options will load in edit UI
      },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const [options, setOptions] = useState([{ label: ep__('Loading…', 'eventprime-event-calendar-management'), value: '0' }]);

      useEffect(() => {
        let mounted = true;
        fetchEvents().then((opts) => {
          if (!mounted) return;
          const safeOpts = opts && opts.length ? opts : [{ label: ep__('No events found', 'eventprime-event-calendar-management'), value: '0' }];
          setOptions(safeOpts);
          // If current eid is unset or invalid, select the first available
          if (!attributes.eid || !safeOpts.find((o) => o.value === attributes.eid)) {
            setAttributes({ eid: safeOpts[0].value });
          }
        });
        return () => {
          mounted = false;
        };
      }, []);

      const changeEid = (eid) => setAttributes({ eid });

      return el('div', {}, [
        el(ServerSideRender, {
          block: 'eventprime-blocks/event-countdown',
          attributes,
        }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'Event Countdown Timer Settings', initialOpen: true },
            el(SelectControl, {
              value: attributes.eid,
              label: ep__('EventPrime Events'),
              help: ep__('Select Event whose countdown timer you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeEid,
              options,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Event Slider Block
  // ---------------------
  registerBlockType('eventprime-blocks/event-slider', {
    title: ep__('EventPrime Event Slider'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    edit() {
      return el('div', {}, [el(ServerSideRender, { block: 'eventprime-blocks/event-slider' })]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Featured Event Organizers
  // ---------------------
  registerBlockType('eventprime-blocks/featured-event-organizers', {
    title: ep__('EventPrime Featured Event Organizers'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/featured-event-organizers', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Featured Event Organizers', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Featured Event Organizers', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of organizers to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of organizers to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Featured Event Performers
  // ---------------------
  registerBlockType('eventprime-blocks/featured-event-performers', {
    title: ep__('EventPrime Featured Event Performers'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/featured-event-performers', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Featured Event Performers', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Featured Event Performers', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of performers to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of performers to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Featured Event Types
  // ---------------------
  registerBlockType('eventprime-blocks/featured-event-types', {
    title: ep__('EventPrime Featured Event Types'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/featured-event-types', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Featured Event Types', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Featured Event Types', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of event types to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of event types to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Featured Event Venues
  // ---------------------
  registerBlockType('eventprime-blocks/featured-event-venues', {
    title: ep__('EventPrime Featured Event Venues'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/featured-event-venues', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Featured Event Venues', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Featured Event Venues', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of venues to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of venues to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Popular Event Organizers
  // ---------------------
  registerBlockType('eventprime-blocks/popular-event-organizers', {
    title: ep__('EventPrime Popular Event Organizers'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/popular-event-organizers', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Popular Event Organizers', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Popular Event Organizers', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of organizers to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of organizers to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Popular Event Performers
  // ---------------------
  registerBlockType('eventprime-blocks/popular-event-performers', {
    title: ep__('EventPrime Popular Event Performers'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/popular-event-performers', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Popular Event Performers', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Popular Event Performers', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of performers to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of performers to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Popular Event Types
  // ---------------------
  registerBlockType('eventprime-blocks/popular-event-types', {
    title: ep__('EventPrime Popular Event Types'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/popular-event-types', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Popular Event Types', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Popular Event Types', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of event types to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of event types to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });

  // ---------------------
  // Popular Event Venues
  // ---------------------
  registerBlockType('eventprime-blocks/popular-event-venues', {
    title: ep__('EventPrime Popular Event Venues'),
    category: 'widgets',
    icon: iconEl,
    supports: { customClassName: false, className: false, html: false },
    attributes: {
      title: { type: 'string', default: '' },
      number: { type: 'string', default: '' },
    },
    edit(props) {
      const { attributes, setAttributes } = props;
      const changeTitle = (title) => setAttributes({ title });
      const changeNumber = (number) => setAttributes({ number });

      return el('div', {}, [
        el(ServerSideRender, { block: 'eventprime-blocks/popular-event-venues', attributes }),
        el(InspectorControls, {}, [
          el(
            PanelBody,
            { title: 'EventPrime Popular Event Venues', initialOpen: true },
            el(TextControl, {
              value: attributes.title,
              label: ep__('EventPrime Popular Event Venues', 'eventprime-event-calendar-management'),
              help: ep__('Enter title you wish to display here.', 'eventprime-event-calendar-management'),
              onChange: changeTitle,
            }),
            el(TextControl, {
              value: attributes.number,
              help: ep__('Number of venues to show', 'eventprime-event-calendar-management'),
              label: ep__('Number of venues to show', 'eventprime-event-calendar-management'),
              onChange: changeNumber,
            })
          ),
        ]),
      ]);
    },
    save() {
      return null;
    },
  });
})(window.wp);
