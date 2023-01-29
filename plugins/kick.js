import { areJidsSameUser } from '@adiwajshing/baileys'
let handler = async (m, { conn, participants }) => {
    if (m.quoted) {
        await conn.groupParticipantsUpdate(m.chat, [m.quoted.sender], 'remove').catch(_ => m.reply('Gagal!'))
    }

    let users = m.mentionedJid.filter(u => !areJidsSameUser(u, conn.user.id))
    let kickedUser = []
    for (let user of users)
        if (user.endsWith('@s.whatsapp.net') && !(participants.find(v => areJidsSameUser(v.id, user)) || { admin: true }).admin) {
            const res = await conn.groupParticipantsUpdate(m.chat, [user], 'remove').catch(_ => m.reply('Gagal!'))
            kickedUser.concat(res)
            await delay(1 * 1000)
        }
    //m.reply(`Succes kick ${kickedUser.map(v => '@' + v.split('@')[0])}`, null, { mentions: kickedUser })

}
handler.help = ['kick', '-'].map(v => 'o' + v + ' @user')
handler.tags = ['admin']
handler.command = /^(kick|-)$/i

handler.admin = true
handler.group = true
handler.botAdmin = true

export default handler

const delay = (ms) => new Promise((resolve) => setTimeout(resolve, ms))
