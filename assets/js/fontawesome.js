import { config, dom, library } from '@fortawesome/fontawesome-svg-core'
import {
  faCamera,
  faCircleA,
  faCompassSlash,
  faEnvelopeOpen,
  faFileArchive,
  faFilePdf,
  faRadar,
  faRefresh
} from '@fortawesome/pro-light-svg-icons'
import '@fortawesome/fontawesome-svg-core/styles.css'

// configure fontawesome
config.autoAddCss = false
library.add(
  faCircleA,
  faFilePdf,
  faFileArchive,
  faEnvelopeOpen,
  faCompassSlash,
  faRadar,
  faCamera,
  faRefresh
)
dom.watch()
