import { generateWAMessageFromContent } from '@adiwajshing/baileys'
let handler = async (m, { conn, text, participants }) => {
    let users = participants.map(u => conn.decodeJid(u.id))
    let q = m.quoted ? m.quoted : m
    let c = m.quoted ? m.quoted : m.msg
    const msg = await conn.cMod(m.chat,
        generateWAMessageFromContent(m.chat, {
            [c.toJSON ? q.mtype : 'extendedTextMessage']: c.toJSON ? c.toJSON() : {
                text: c || ''
            }
        }, {
            quoted: false,
            userJid: conn.user.id
        }),
        text || q.text, conn.user.jid, { mentions: users }
    )

    //console.log(msg)
    await conn.relayMessage(m.chat, msg.message, { messageId: msg.key.id })
}
handler.help = ['pengumuman', 'announce', 'hidetag'].map(v => v + ' [teks]')
handler.tags = ['group']
handler.command = /^(pengumuman|announce|hiddentag|h(ide)?tag)$/i

handler.group = true
handler.admin = true

export default handler