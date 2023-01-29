let handler = async (m, { conn }) => {
    let emot = ["😆", "😅", "😂", "🥲", "☺️", "😇", "😌", "😋", "😛", "🤨", "😎", "🤩", "🥳", "😒", "😣", "🥺", "😤", "😡", "🥵", "😱", "😴", "🤡", "☠️", "👍", "👎", "👀", "🗿", "🚀", "🔥"].getRandom();
    conn.sendMessage(m.chat, {
        react: {
            text: emot,
            key: m.key
        }
    })
}
handler.customPrefix = /^(bilek|banh|cum|kntl|y|mmk|p|baco(t|d)|wibu|pantek|pepek|an?jg|crot)$/i
handler.command = new RegExp

//['bilek', 'banh', 'cum', 'crot', 'p', 'y', 'kontol', 'kntl', 'mmk', 'memek', 'anjg', 'ajg']
export default handler