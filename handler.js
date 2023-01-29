import { smsg } from './lib/simple.js'
import { format } from 'util'
import { fileURLToPath } from 'url'
import path, { join } from 'path'
import a from './lib/print.js'
import fetch from 'node-fetch'
import { watchFile, unwatchFile, fstat, readFileSync } from 'fs'
import chalk from 'chalk'

function clockString(ms) {
    let remain = ms

    let d = isNaN(ms) ? '--' : Math.floor(remain / 86400000) + ' Hari'
    remain = remain % (1000 * 60 * 60 * 24)
    let h = isNaN(ms) ? '--' : Math.floor(remain / 3600000) + ' Jam'
    remain = remain % (1000 * 60 * 60)
    let m = isNaN(ms) ? '--' : Math.floor(remain / 60000) % 60 + ' Min'
    remain = remain % (1000 * 60)
    let s = isNaN(ms) ? '--' : Math.floor(remain / 1000) % 60 + ' Dtk'
    remain = remain % (1000)
    return [d, h, m, s].map(v => v.toString().padStart(2, 0)).join(', ')
}
const isNumber = x => typeof x === 'number' && !isNaN(x)
const delay = ms => isNumber(ms) && new Promise(resolve => setTimeout(function () {
    clearTimeout(this)
    resolve()
}, ms))

export async function handler(chatUpdate) {
    this.msgqueque = this.msgqueque || []
    if (!chatUpdate)
        return
    this.pushMessage(chatUpdate.messages).catch(console.error)
    let m = chatUpdate.messages[chatUpdate.messages.length - 1]
    if (!m)
        return
    if (m.messageStubType == 2) console.log(m)
    if (global.db.data == null)
        await global.loadDatabase()
    try {
        m = smsg(this, m) || m
        if (!m)
            return
        m.exp = 0
        m.limit = false
        try {
            // TODO: use loop to insert data instead of this
            let user = global.db.data.users[m.sender]
            if (typeof user !== 'object')
                global.db.data.users[m.sender] = {}
            if (user) {
                if (!isNumber(user.exp)) user.exp = 0
                if (!isNumber(user.limit)) user.limit = 10
                if (!isNumber(user.lastclaim)) user.lastclaim = 0
                if (!('registered' in user)) user.registered = false
                if (!user.registered) {
                    if (!('name' in user)) user.name = this.getName(m.sender)
                    if (!isNumber(user.age)) user.age = -1
                    if (!isNumber(user.regTime)) user.regTime = -1
                }
                if (!isNumber(user.afk)) user.afk = -1
                if (!('afkReason' in user)) user.afkReason = ''
                if (!('banned' in user)) user.banned = false
                if (!isNumber(user.level)) user.level = 0
                if (!isNumber(user.call)) user.call = 0
                if (!user.role) user.role = 'Bronze'
                if (!('autolevelup' in user)) user.autolevelup = false
                if (!isNumber(user.pc)) user.pc = 0
                if (!isNumber(user.warning)) user.warning = 0
                if (!('pasangan' in user)) user.pasangan = ''
            } else
                global.db.data.users[m.sender] = {
                    exp: 0,
                    limit: 10,
                    lastclaim: 0,
                    registered: false,
                    name: this.getName(m.sender),
                    age: -1,
                    regTime: -1,
                    afk: -1,
                    afkReason: '',
                    banned: false,
                    level: 0,
                    call: 0,
                    role: 'Bronze',
                    autolevelup: false,
                    pc: 0,
                    warning: 0,
                    pasangan: ''
                }
            let chat = global.db.data.chats[m.chat]
            if (typeof chat !== 'object')
                global.db.data.chats[m.chat] = {}
            if (chat) {
                if (!('isBanned' in chat)) chat.isBanned = false
                if (!('welcome' in chat)) chat.welcome = false
                if (!('detect' in chat)) chat.detect = false
                if (!('delete' in chat)) chat.download = true
                if (!('sWelcome' in chat)) chat.sWelcome = ''
                if (!('sBye' in chat)) chat.sBye = ''
                if (!('sPromote' in chat)) chat.sPromote = ''
                if (!('sDemote' in chat)) chat.sDemote = ''
                if (!('descUpdate' in chat)) chat.descUpdate = true
                if (!('stiker' in chat)) chat.stiker = false
                if (!('delete' in chat)) chat.delete = true
                if (!('antiLink' in chat)) chat.antiLink = false
                if (!isNumber(chat.expired)) chat.expired = 0
                if (!('antiBadword' in chat)) chat.antiBadword = true
                if (!('viewonce' in chat)) chat.viewonce = true
            } else global.db.data.chats[m.chat] = {
                isBanned: false,
                welcome: false,
                detect: false,
                delete: true,
                sWelcome: '',
                sBye: '',
                sPromote: '',
                sDemote: '',
                descUpdate: true,
                stiker: false,
                delete: true,
                antiLink: false,
                expired: 0,
                antiBadword: true,
                viewonce: true,
            }
            let settings = global.db.data.settings[this.user.jid]
            if (typeof settings !== 'object') global.db.data.settings[this.user.jid] = {}
            if (settings) {
                if (!'anon' in settings) settings.anon = true
                if (!'anticall' in settings) settings.anticall = true
                if (!'antispam' in settings) settings.antispam = true
                if (!'antitroli' in settings) settings.antitroli = true
                if (!'backup' in settings) settings.backup = false
                if (!isNumber(settings.backupDB)) settings.backupDB = 0
                if (!'groupOnly' in settings) settings.groupOnly = false
                if (!'jadibot' in settings) settings.groupOnly = false
                if (!'nsfw' in settings) settings.nsfw = false
                if (!isNumber(settings.status)) settings.status = 0
            } else global.db.data.settings[this.user.jid] = {
                anon: true,
                anticall: true,
                antispam: true,
                antitroli: true,
                backup: false,
                backupDB: 0,
                groupOnly: false,
                jadibot: false,
                nsfw: true,
                status: 0,
            }
        } catch (e) {
            console.error(e)
        }
        if (opts['nyimak'])
            return
        let data = global.owner.filter(([number, isCreator]) => number && isCreator)
        if (!m.fromMe && m.sender !== (owner[0][0] + '@s.whatsapp.net' || owner[1][0] + '@s.whatsapp.net') && opts['self'])
            return
        if (opts['pconly'] && m.chat.endsWith('g.us'))
            return
        if (opts['gconly'] && !m.chat.endsWith('g.us'))
            return
        /*if (opts['swonly'] && m.chat !== 'status@broadcast')
            return*/
        if (m.chat === 'status@broadcast') return
        if (typeof m.text !== 'string')
            m.text = ''

        const isROwner = [conn.decodeJid(global.conn.user.id), ...global.owner.map(([number]) => number)].map(v => v.replace(/[^0-9]/g, '') + '@s.whatsapp.net').includes(m.sender)
        const isOwner = isROwner || m.fromMe
        const isMods = isOwner || global.mods.map(v => v.replace(/[^0-9]/g, '') + '@s.whatsapp.net').includes(m.sender)
        const isPrems = isROwner || global.prems.map(v => v.replace(/[^0-9]/g, '') + '@s.whatsapp.net').includes(m.sender)

        if (opts['queque'] && m.text && !(isMods || isPrems)) {
            let queque = this.msgqueque, time = 1000 * 5
            const previousID = queque[queque.length - 1]
            queque.push(m.id || m.key.id)
            setInterval(async function () {
                if (queque.indexOf(previousID) === -1) clearInterval(this)
                await delay(time)
            }, time)
        }

        if (m.isBaileys)
            return
        m.exp += Math.ceil(Math.random() * 10)

        let usedPrefix
        let _user = global.db.data && global.db.data.users && global.db.data.users[m.sender]

        const groupMetadata = (m.isGroup ? ((conn.chats[m.chat] || {}).metadata || await this.groupMetadata(m.chat).catch(_ => null)) : {}) || {}
        const participants = (m.isGroup ? groupMetadata.participants : []) || []
        const user = (m.isGroup ? participants.find(u => conn.decodeJid(u.id) === m.sender) : {}) || {} // User Data
        const bot = (m.isGroup ? participants.find(u => conn.decodeJid(u.id) == this.user.jid) : {}) || {} // Your Data
        const isRAdmin = user?.admin == 'superadmin' || false
        const isAdmin = isRAdmin || user?.admin == 'admin' || false // Is User Admin?
        const isBotAdmin = bot?.admin || false // Are you Admin?

        const ___dirname = path.join(path.dirname(fileURLToPath(import.meta.url)), './plugins')
        for (let name in global.plugins) {
            let plugin = global.plugins[name]
            if (!plugin)
                continue
            if (plugin.disabled)
                continue
            if (typeof plugin.all === 'function') {
                try {
                    await plugin.all.call(this, m, {
                        chatUpdate,
                        __dirname: ___dirname,
                        __filename: join(___dirname, name)
                    })
                } catch (e) {
                    // if (typeof e === 'string') continue
                    console.error(e)
                }
            }
            if (!opts['restrict'])
                if (plugin.tags && plugin.tags.includes('admin')) {
                    // global.dfail('restrict', m, this)
                    continue
                }
            const str2Regex = str => str.replace(/[|\\{}()[\]^$+*?.]/g, '\\$&')
            let _prefix = plugin.customPrefix ? plugin.customPrefix : conn.prefix ? conn.prefix : global.prefix
            let match = (_prefix instanceof RegExp ? // RegExp Mode?
                [[_prefix.exec(m.text), _prefix]] :
                Array.isArray(_prefix) ? // Array?
                    _prefix.map(p => {
                        let re = p instanceof RegExp ? // RegExp in Array?
                            p :
                            new RegExp(str2Regex(p))
                        return [re.exec(m.text), re]
                    }) :
                    typeof _prefix === 'string' ? // String?
                        [[new RegExp(str2Regex(_prefix)).exec(m.text), new RegExp(str2Regex(_prefix))]] :
                        [[[], new RegExp]]
            ).find(p => p[1])
            if (typeof plugin.before === 'function')
                if (await plugin.before.call(this, m, {
                    match,
                    conn: this,
                    participants,
                    groupMetadata,
                    user,
                    bot,
                    isROwner,
                    isOwner,
                    isRAdmin,
                    isAdmin,
                    isBotAdmin,
                    isPrems,
                    chatUpdate,
                    __dirname: ___dirname,
                    __filename: join(___dirname, name)
                }))
                    continue
            if (typeof plugin !== 'function')
                continue
            if ((usedPrefix = (match[0] || '')[0])) {
                let noPrefix = m.text.replace(usedPrefix, '')
                let [command, ...args] = noPrefix.trim().split` `.filter(v => v)
                args = args || []
                let _args = noPrefix.trim().split` `.slice(1)
                let text = _args.join` `
                command = (command || '').toLowerCase()
                let fail = plugin.fail || global.dfail // When failed
                let isAccept = plugin.command instanceof RegExp ? // RegExp Mode?
                    plugin.command.test(command) :
                    Array.isArray(plugin.command) ? // Array?
                        plugin.command.some(cmd => cmd instanceof RegExp ? // RegExp in Array?
                            cmd.test(command) :
                            cmd === command
                        ) :
                        typeof plugin.command === 'string' ? // String?
                            plugin.command === command :
                            false

                if (!isAccept)
                    continue
                m.plugin = name
                if (m.chat in global.db.data.chats || m.sender in global.db.data.users) {
                    let chat = global.db.data.chats[m.chat]
                    let user = global.db.data.users[m.sender]
                    if (!['unbanchat.js', 'profile.js', '_autoreact.js'].includes(name) && chat && chat.isBanned && !isPrems) return // Kecuali ini, bisa digunakan
                    if (!['unbanchat.js', 'profile.js'].includes(name) && user && user.banned) return
                }
                if (plugin.rowner && plugin.owner && !(isROwner || isOwner)) { // Both Owner
                    fail('owner', m, this)
                    continue
                }
                if (plugin.rowner && !isROwner) { // Real Owner
                    fail('rowner', m, this)
                    continue
                }
                if (plugin.owner && !isOwner) { // Number Owner
                    fail('owner', m, this)
                    continue
                }
                if (plugin.mods && !isMods) { // Moderator
                    fail('mods', m, this)
                    continue
                }
                if (plugin.premium && !isPrems) { // Premium
                    fail('premium', m, this)
                    continue
                }
                if (plugin.group && !m.isGroup) { // Group Only
                    fail('group', m, this)
                    continue
                } else if (plugin.botAdmin && !isBotAdmin) { // You Admin
                    fail('botAdmin', m, this)
                    continue
                } else if (plugin.admin && !isAdmin) { // User Admin
                    fail('admin', m, this)
                    continue
                }
                if (plugin.private && m.isGroup) { // Private Chat Only
                    fail('private', m, this)
                    continue
                }
                if (plugin.register == true && _user.registered == false) { // Butuh daftar?
                    fail('unreg', m, this)
                    continue
                }
                m.isCommand = true
                let xp = 'exp' in plugin ? parseInt(plugin.exp) : 17 // XP Earning per command
                if (xp > 200)
                    m.reply('Ngecit -_-') // Hehehe
                else
                    m.exp += xp
                if (!isPrems && plugin.limit && global.db.data.users[m.sender].limit < plugin.limit * 1) {
                    this.reply(m.chat, `Limit anda habis, silahkan beli melalui *${usedPrefix}buy*`, m)
                    continue // Limit habis
                }
                if (plugin.level > _user.level) {
                    this.reply(m.chat, `diperlukan level ${plugin.level} untuk menggunakan perintah ini. Level kamu ${_user.level}`, m)
                    continue // If the level has not been reached
                }
                let extra = {
                    match,
                    usedPrefix,
                    noPrefix,
                    _args,
                    args,
                    command,
                    text,
                    conn: this,
                    participants,
                    groupMetadata,
                    user,
                    bot,
                    isROwner,
                    isOwner,
                    isRAdmin,
                    isAdmin,
                    isBotAdmin,
                    isPrems,
                    chatUpdate,
                    __dirname: ___dirname,
                    __filename: join(___dirname, name)
                }
                try {
                    await plugin.call(this, m, extra)
                    if (!isPrems)
                        m.limit = m.limit || plugin.limit || false
                } catch (e) {
                    // Error occured
                    m.error = e
                    console.error(e)
                } finally {
                    // m.reply(util.format(_user))
                    if (typeof plugin.after === 'function') {
                        try {
                            await plugin.after.call(this, m, extra)
                        } catch (e) {
                            console.error(e)
                        }
                    }
                    //if (m.limit)
                    //m.reply(+m.limit + ' Limit terpakai')
                }
                break
            }
        }
    } catch (e) {
        console.error(e)
    } finally {
        if (opts['queque'] && m.text) {
            const quequeIndex = this.msgqueque.indexOf(m.id || m.key.id)
            if (quequeIndex !== -1)
                this.msgqueque.splice(quequeIndex, 1)
        }
        //console.log(global.db.data.users[m.sender])
        let user, stats = global.db.data.stats
        if (m) {
            if (m.sender && (user = global.db.data.users[m.sender])) {
                user.exp += m.exp
                user.limit -= m.limit * 1
            }

            let stat
            if (m.plugin) {
                let now = +new Date
                if (m.plugin in stats) {
                    stat = stats[m.plugin]
                    if (!isNumber(stat.total))
                        stat.total = 1
                    if (!isNumber(stat.success))
                        stat.success = m.error != null ? 0 : 1
                    if (!isNumber(stat.last))
                        stat.last = now
                    if (!isNumber(stat.lastSuccess))
                        stat.lastSuccess = m.error != null ? 0 : now
                } else
                    stat = stats[m.plugin] = {
                        total: 1,
                        success: m.error != null ? 0 : 1,
                        last: now,
                        lastSuccess: m.error != null ? 0 : now
                    }
                stat.total += 1
                stat.last = now
                if (m.error == null) {
                    stat.success += 1
                    stat.lastSuccess = now
                }
            }
        }

        try {
            a(m, this)
        } catch (e) {
            console.log(m, m.quoted, e)
        }
        //await this.sendPresenceUpdate('unavailable', m.chat)
        //if (opts['autoread'])
        await this.readMessages([m.key])
        //await this.chatRead(m.chat, m.isGroup ? m.sender : undefined, m.id || m.key.id).catch(() => { })
    }
}
export async function participantsUpdate({ id, participants, action }) {
    if (opts['self'])
        return
    // if (id in conn.chats) return // First login will spam
    if (global.isInit)
        return
    if (global.db.data == null)
        await loadDatabase()
    let chat = global.db.data.chats[id] || {}
    let text = ''
    switch (action) {
        case 'add':
        case 'remove':
            if (chat.welcome) {
                let groupMetadata = await this.groupMetadata(id) || (conn.chats[id] || {}).metadata
                for (let user of participants) {
                    let pp = fs.readFileSync('./src/avatar_contact.png')
                    try {
                        pp = await (await fetch(await this.profilePictureUrl(user, 'image'))).buffer()
                    } catch (e) {
                    } finally {
                        let _uptime = process.uptime() * 1000
                        let uptime = clockString(_uptime)
                        text = (action === 'add' ? (chat.sWelcome || this.welcome || conn.welcome || 'Selamat datang, @user!').replace('@subject', await this.getName(id)).replace('@desc', groupMetadata.desc ? String.fromCharCode(8206).repeat(4001) + groupMetadata.desc : '') :
                            (chat.sBye || this.bye || conn.bye || 'Sampai jumpa, @user!')).replace(/@user/g, await this.getName(user))
                        this.reply(id, text, false, {
                            contextInfo: {
                                externalAdReply: {
                                    title: wm,
                                    body: `aktif selama: ${uptime}`,
                                    thumbnail: pp,
                                    sourceUrl: 'https://saweria.co/ZanzBot'
                                }
                            }
                        })
                    }
                }
            }
            break
        case 'promote':
            text = (chat.sPromote || this.spromote || conn.spromote || '@user ```is now Admin```')
        case 'demote':
            if (!text)
                text = (chat.sDemote || this.sdemote || conn.sdemote || '@user ```is no longer Admin```')
            text = text.replace('@user', '@' + participants[0].split('@')[0])
            if (chat.detect)
                this.sendMessage(id, { text, mentions: this.parseMention(text) })
            break
    }
}
export async function deleteUpdate({ remoteJid, fromMe, id, participant }) {
    if (fromMe) return
    let chats = Object.entries(conn.chats).find(([user, data]) => data.messages && data.messages[id])
    if (!chats) return
    let msg = JSON.parse(chats[1].messages[id])
    let chat = global.db.data.chats[msg.key.remoteJid] || {}
    if (chat.delete) return
    this.sendButton(msg.key.remoteJid, `
Terdeteksi @${participant.split`@`[0]} telah menghapus pesan`, wm, false, [['Matikan', '.on delete']])
    //this.copyNForward(msg.key.remoteJid, msg).catch(e => console.log(e, msg))
}

global.dfail = (type, m, conn) => {
    let msg = {
        rowner: 'Perintah ini hanya dapat digunakan oleh _*OWWNER!1!1!*_',
        owner: 'Perintah ini hanya dapat digunakan oleh _*Owner Bot*_!',
        mods: 'Perintah ini hanya dapat digunakan oleh _*Moderator*_ !',
        premium: 'Perintah ini hanya untuk member _*Premium*_ !',
        group: 'Perintah ini hanya dapat digunakan di grup!',
        private: 'Perintah ini hanya dapat digunakan di Chat Pribadi!',
        admin: 'Perintah ini hanya untuk *Admin* grup!',
        botAdmin: 'Jadikan bot sebagai *Admin* untuk menggunakan perintah ini!',
        unreg: 'Silahkan daftar untuk menggunakan fitur ini dengan cara mengetik:\n\n*#daftar nama.umur*\n\nContoh: *#daftar Manusia.16*',
        restrict: 'Fitur ini di *disable*!'
    }[type]
    if (msg) return m.reply(msg)
}


let file = global.__filename(import.meta.url, true)
watchFile(file, async () => {
    unwatchFile(file)
    console.log(chalk.redBright("Update 'handler.js'"))
    if (global.reloadHandler) console.log(await global.reloadHandler())
})
