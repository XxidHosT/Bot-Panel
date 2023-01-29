import fs, { watchFile, unwatchFile, readFileSync } from 'fs';
import chalk from 'chalk';
import { fileURLToPath } from 'url';

global.owner = [
  ['ubah nomor anda', '⚡⚡⚡', true],
]
// [number, dia creator/owner?, dia developer?]
// Put your number here
global.mods = ['ubah nomor anda'] // Want some help?
global.prems = JSON.parse(readFileSync('./src/premium.json')) // Premium user has unlimited limit
global.wm = 'PANEL XXID'

let file = fileURLToPath(import.meta.url)
watchFile(file, () => {
  unwatchFile(file)
  console.log(chalk.redBright("Update 'config.js'"))
  import(file)
})
