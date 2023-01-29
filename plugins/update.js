import { execSync } from 'child_process'
import { readdirSync } from 'fs'
let handler = async (m, { conn, text, isROwner }) => {
    if (global.conn.user.jid == conn.user.jid) {
        let stdout = execSync('git remote set-url origin https://github.com/aniq12/PatrickMD-Test.git && git pull' + (isROwner && text ? ' ' + text : ''))
        if (isROwner) readdirSync('plugins').map(v => global.reload('', v))
        m.reply(stdout.toString())
    }
}
handler.help = ['update']
handler.tags = ['host']
handler.command = /^u(pdate)?$/i

handler.owner = true

export default handler