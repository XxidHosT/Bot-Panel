let handler = function (m) {
    if (!m.quoted) throw false
    let { chat, fromMe, id, isBaileys } = m.quoted
    let { key } = m.quoted.fakeObj
    if (!fromMe) throw false
    if (/PatrickBot Broadcast/i.test(m.quoted.text)) throw 'Tidak bisa menghapus pesan broadcast!'
    if (!isBaileys) throw 'Pesan tersebut bukan dikirim oleh bot!'
    this.sendMessage(chat, {
        delete: key
    })
}
handler.help = ['del', 'delete']
handler.tags = ['info']

handler.command = /^del(ete)?$/i

export default handler