let handler = async (m, { conn, text, participants }) => {
    let _participants = participants.map(user => user.id)
    let users = (await Promise.all(
        text.split(',')
            .map(v => v.replace(/[^0-9]/g, ''))
            .filter(v => v.length > 4 && v.length < 20 && !_participants.includes(v + '@s.whatsapp.net'))
            .map(async v => [
                v,
                await conn.onWhatsApp(v + '@s.whatsapp.net')
            ])
    )).filter(v => v[1][0]?.exists).map(v => v[0] + '@c.us')
    const response = await conn.groupParticipantsUpdate(m.chat, users, 'add').catch(_ => m.reply('Gagal!'))
    //m.reply(`Succes add ${response.map(v => '@' + v.split('@')[0])}`, null, { mentions: response })
}
handler.help = ['add', '+'].map(v => 'o' + v + ' @user')
handler.tags = ['admin']
handler.command = /^(add|\+)$/i

handler.admin = true
handler.group = true
handler.botAdmin = true


export default handler
