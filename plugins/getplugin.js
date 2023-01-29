import cp from 'child_process'
import { readFileSync, readdirSync } from 'fs'
import { promisify } from 'util'
let exec = promisify(cp.exec).bind(cp)

let handler = async (m, { conn, isROwner, usedPrefix, command, text }) => {
    if (!isROwner) return
    let ar = readdirSync(`./plugins`)
    let ar1 = ar.map(v => v.replace('.js', ''))
    if (!text) throw `Uhm.. where the text?\n\nexample:\n${usedPrefix + command} menu`
    if (!ar1.includes(text)) return m.reply(`Not found!\n\n${ar1.map(v => ' ' + v).join`\n`}`)

    m.reply(readFileSync('./plugins/' + text + '.js', 'utf-8'))
}
handler.help = ['getplugin'].map(v => v + ' <text>')
handler.tags = ['owner']
handler.command = /^(getplugin|gp)$/i

export default handler