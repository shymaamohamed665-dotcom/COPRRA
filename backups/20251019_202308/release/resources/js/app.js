import './bootstrap';
import './animations/image-effects';

import { registerSW } from 'virtual:pwa-register'
registerSW({ immediate: true })
