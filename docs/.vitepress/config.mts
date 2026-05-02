import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'YoutubeLaravelApi',
  description: 'Modern Laravel wrapper for the YouTube Data API v3',

  base: '/YoutubeLaravelApi/',

  head: [
    ['link', { rel: 'icon', href: '/YoutubeLaravelApi/favicon.svg' }],
    ['meta', { name: 'theme-color', content: '#FF0000' }],
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'API', link: '/api/' },
      { text: 'Examples', link: '/examples/creating-a-broadcast' },
      { text: 'Upgrading', link: '/upgrading/from-1.x' },
      {
        text: 'v2.0.0',
        items: [
          { text: 'Changelog', link: 'https://github.com/alchemyguy/YoutubeLaravelApi/blob/master/CHANGELOG.md' },
          { text: 'Releases', link: 'https://github.com/alchemyguy/YoutubeLaravelApi/releases' },
        ],
      },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting started',
          items: [
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Authentication', link: '/guide/authentication' },
          ],
        },
        {
          text: 'Features',
          items: [
            { text: 'Live streaming', link: '/guide/live-streaming' },
            { text: 'Channels', link: '/guide/channels' },
            { text: 'Videos', link: '/guide/videos' },
          ],
        },
        {
          text: 'Reference',
          items: [
            { text: 'Error handling', link: '/guide/error-handling' },
            { text: 'Testing', link: '/guide/testing' },
          ],
        },
      ],
      '/api/': [{ text: 'API Reference', items: [] }],
      '/examples/': [
        {
          text: 'Examples',
          items: [
            { text: 'Creating a broadcast', link: '/examples/creating-a-broadcast' },
            { text: 'Uploading a video', link: '/examples/uploading-a-video' },
            { text: 'Multi-account', link: '/examples/multi-account' },
          ],
        },
      ],
      '/upgrading/': [
        {
          text: 'Upgrading',
          items: [{ text: 'From 1.x to 2.0', link: '/upgrading/from-1.x' }],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/alchemyguy/YoutubeLaravelApi' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2018–present Mukesh Chandra',
    },

    search: { provider: 'local' },
  },
})
