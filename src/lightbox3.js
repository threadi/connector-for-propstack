import { Lightbox } from 'lightbox3';
import '../node_modules/lightbox3/dist/lightbox3.css';

document.addEventListener('DOMContentLoaded', () => {
  Lightbox.init( {
      'selector': '.cfprop-gallery .gallery .gallery-item a'
    }
  );
});
