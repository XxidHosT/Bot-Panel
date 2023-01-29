let handler = async (m, { conn, usedPrefix, args, command }) => conn.sendMessage(m.chat, {
    react: {
        text: args[0],
        key: m.quoted ? m.quoted.fakeObj.key : m.key
    }
});


handler.help = ['']
handler.tags = ['']
handler.command = ['react', 'r']

export default handler
