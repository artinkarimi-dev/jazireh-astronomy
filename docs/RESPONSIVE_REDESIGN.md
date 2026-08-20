# Responsive Redesign — V2

## Design direction

The public interface was rebuilt around a restrained black astronomy background. The client-provided vertical starfield video is rendered once as a fixed global background, rather than being repeated as a large hero on every page.

## Desktop behavior

- Content uses a 12-column bento grid.
- Important text, metrics, tools, and actions appear side by side.
- No single decorative image or video occupies an entire desktop section.
- Interactive tools such as the solar-system scene, sky map, APOD, video player, and radar use dedicated two-column layouts.
- The old fixed desktop sidebar was removed to recover horizontal space.

## Tablet behavior

- Navigation moves into the header drawer.
- Large two-column sections collapse only when the available width is no longer comfortable.
- Cards switch from four columns to two columns.

## Mobile behavior

- Content becomes a single readable sequence.
- A compact bottom navigation is enabled.
- Media remains bounded by aspect ratio and never exceeds the viewport.
- Spacing accounts for the fixed mobile navigation.

## Media policy

- The high-resolution PNG logo is preserved in the repository.
- An optimized WebP derivative is used in the interface for speed without visible quality loss.
- The starfield background video was optimized for web delivery.
- The large cinematic video was compressed for the video library.
- The APOD demo uses NASA's high-resolution Webb image of the Cosmic Cliffs in the Carina Nebula, credited to NASA, ESA, CSA, and STScI.
- Low-resolution decorative images are no longer used in page heroes or news cards.

## Performance decisions

- Only one autoplaying background video is mounted in the public layout.
- Background video uses `preload="metadata"`.
- Decorative images were replaced by CSS and icon-driven visuals where media did not add meaning.
- Route-level lazy loading from the original project remains enabled.
- No additional smooth-scroll dependency was introduced; native scrolling is retained for performance and accessibility.
