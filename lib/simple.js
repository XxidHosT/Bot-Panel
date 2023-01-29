import path from 'path'
import jimp from 'jimp'
import { toAudio } from './converter.js'
import chalk from 'chalk'
import fetch from 'node-fetch'
import PhoneNumber from 'awesome-phonenumber'
import fs from 'fs'
import util from 'util'
import { fileTypeFromBuffer } from 'file-type'
import { format } from 'util'
import { fileURLToPath } from 'url'
import store from './store.js'

const __dirname = path.dirname(fileURLToPath(import.meta.url))

const {
    default: _makeWaSocket,
    makeWALegacySocket,
    proto,
    downloadContentFromMessage,
    jidDecode,
    areJidsSameUser,
    generateForwardMessageContent,
    generateWAMessageFromContent,
    WAMessageStubType
} = (await import('@adiwajshing/baileys')).default

export function makeWASocket(...args) {
    /**
     * @type {import('@adiwajshing/baileys').WASocket | import('@adiwajshing/baileys').WALegacySocket}
     */
    let conn = (opts['legacy'] ? makeWALegacySocket : _makeWaSocket)(...args)

    conn.decodeJid = (jid) => {
        if (!jid) return jid
        if (/:\d+@/gi.test(jid)) {
            const decode = jidDecode(jid) || {}
            return (decode.user && decode.server && decode.user + '@' + decode.server || jid).trim()
        } else return jid.trim()
    }
    if (conn.user && conn.user.id) conn.user.jid = conn.decodeJid(conn.user.id)
    store.bind(conn)

    conn.logger = {
        ...conn.logger,
        info(...args) {
            console.log(
                chalk.bold.bgRgb(51, 204, 51)('INFO '),
                `[${chalk.rgb(255, 255, 255)(new Date().toUTCString())}]:`,
                chalk.cyan(format(...args))
            )
        },
        error(...args) {
            console.log(
                chalk.bold.bgRgb(247, 38, 33)('ERROR '),
                `[${chalk.rgb(255, 255, 255)(new Date().toUTCString())}]:`,
                chalk.rgb(255, 38, 0)(format(...args))
            )
        },
        warn(...args) {
            console.log(
                chalk.bold.bgRgb(255, 153, 0)('WARNING '),
                `[${chalk.rgb(255, 255, 255)(new Date().toUTCString())}]:`,
                chalk.redBright(format(...args))
            )
        }
    }



    /**
     * getBuffer hehe
     * @param {fs.PathLike} path
     * @param {Boolean} returnFilename
     */
    conn.getFile = async (PATH, returnAsFilename) => {
        let res, filename
        const data = Buffer.isBuffer(PATH) ? PATH : PATH instanceof ArrayBuffer ? PATH.toBuffer() : /^data:.*?\/.*?;base64,/i.test(PATH) ? Buffer.from(PATH.split`,`[1], 'base64') : /^https?:\/\//.test(PATH) ? await (res = await fetch(PATH)).buffer() : fs.existsSync(PATH) ? (filename = PATH, fs.readFileSync(PATH)) : typeof PATH === 'string' ? PATH : Buffer.alloc(0)
        if (!Buffer.isBuffer(data)) throw new TypeError('Result is not a buffer')
        const type = await fileTypeFromBuffer(data) || {
            mime: 'application/octet-stream',
            ext: '.bin'
        }
        if (data && returnAsFilename && !filename) (filename = path.join(__dirname, '../tmp/' + new Date * 1 + '.' + type.ext), await fs.promises.writeFile(filename, data))
        return {
            res,
            filename,
            ...type,
            data,
            deleteFile() {
                return filename && fs.promises.unlink(filename)
            }
        }
    }

    /**
    * waitEvent
    * @param {String} eventName 
    * @param {Boolean} is 
    * @param {Number} maxTries 
    * @returns 
    */
    conn.waitEvent = (eventName, is = () => true, maxTries = 25) => {
        return new Promise((resolve, reject) => {
            let tries = 0
            let on = (...args) => {
                if (++tries > maxTries) reject('Max tries reached')
                else if (is()) {
                    conn.ev.off(eventName, on)
                    resolve(...args)
                }
            }
            conn.ev.on(eventName, on)
        })
    }

    /**
    * Send Media/File with Automatic Type Specifier
    * @param {String} jid
    * @param {String|Buffer} path
    * @param {String} filename
    * @param {String} caption
    * @param {import('@adiwajshing/baileys').proto.WebMessageInfo} quoted
    * @param {Boolean} ptt
    * @param {Object} options
    */
    conn.sendFile = async (jid, path, filename = '', caption = '', quoted, ptt = false, options = {}) => {
        let type = await conn.getFile(path, true)
        let { res, data: file, filename: pathFile } = type
        if (res && res.status !== 200 || file.length <= 65536) {
            try { throw { json: JSON.parse(file.toString()) } }
            catch (e) { if (e.json) throw e.json }
        }
        let opt = { filename }
        if (quoted) opt.quoted = quoted
        if (!type) options.asDocument = true
        let mtype = '', mimetype = options.mimetype || type.mime, convert
        if (/webp/.test(type.mime) || (/image/.test(type.mime) && options.asSticker)) mtype = 'sticker'
        else if (/image/.test(type.mime) || (/webp/.test(type.mime) && options.asImage)) mtype = 'image'
        else if (/video/.test(type.mime)) mtype = 'video'
        else if (/audio/.test(type.mime)) (
            convert = await toAudio(file, type.ext),
            file = convert.data,
            pathFile = convert.filename,
            mtype = 'audio',
            mimetype = options.mimetype || 'audio/ogg; codecs=opus'
        )
        else mtype = 'document'
        if (options.asDocument) mtype = 'document'

        delete options.asSticker
        delete options.asLocation
        delete options.asVideo
        delete options.asDocument
        delete options.asImage

        let message = {
            ...options,
            caption,
            ptt,
            [mtype]: { url: pathFile },
            mimetype
        }
        /**
         * @type {import('@adiwajshing/baileys').proto.WebMessageInfo}
         */
        let m
        try {
            m = await conn.sendMessage(jid, message, { ...opt, ...options })
        } catch (e) {
            console.error(e)
            m = null
        } finally {
            if (!m) m = await conn.sendMessage(jid, { ...message, [mtype]: file }, { ...opt, ...options })
            return m
        }
    }

    /**
     * Send Sticker Image/Video
     * @param {String} jid
     * @param {String|Buffer} path
     * @param {Object} options
     */

    //━━━━━[ CREATE STICKER IMG ]━━━━━//
    conn.sendSimg = async (path, options = {}) => {
        let buff = Buffer.isBuffer(path) ? path : /^data:.?\/.?;base64,/i.test(path) ? Buffer.from(path.split`,`[1], 'base64') : /^https?:\/\//.test(path) ? await (await fetch(path)).buffer() : fs.existsSync(path) ? fs.readFileSync(path) : Buffer.alloc(0)

        let buffer
        if (options && (options.packname || options.author)) {
            buffer = await writeExifImg(buff, options)
        } else {
            buffer = await imageToWebp(buff)
        }
        await conn.sendMessage(jid, { sticker: { url: buffer }, ...options }, { quoted })
        return buffer
    }

    //━━━━━[ CREATE STICKER VID ]━━━━━//
    conn.sendSvid = async (jid, path, quoted, options = {}) => {
        let buff = Buffer.isBuffer(path) ? path : /^data:.?\/.?;base64,/i.test(path) ? Buffer.from(path.split`,`[1], 'base64') : /^https?:\/\//.test(path) ? await getBuffer(path) : fs.existsSync(path) ? fs.readFileSync(path) : Buffer.alloc(0)

        let buffer
        if (options && (options.packname || options.author)) {
            buffer = await writeExifVid(buff, options)
        } else {
            buffer = await videoToWebp(buff)
        }
        await conn.sendMessage(jid, { sticker: { url: buffer }, ...options }, { quoted })
        return buffer
    }

    /**
     * Send Contact
     * @param {String} jid 
     * @param {String[][]|String[]} data
     * @param {import('@adiwajshing/baileys').proto.WebMessageInfo} quoted 
     * @param {Object} options 
     */
    conn.sendContact = async (jid, data, quoted, options) => {
        if (!Array.isArray(data[0]) && typeof data[0] === 'string') data = [data]
        let contacts = []
        for (let [number, name] of data) {
            number = number.replace(/[^0-9]/g, '')
            let njid = number + '@s.whatsapp.net'
            let biz = await conn.getBusinessProfile(njid).catch(_ => null) || {}
            let vcard = `
BEGIN:VCARD
VERSION:3.0
N:;${name.replace(/\n/g, '\\n')};;;
FN:${name.replace(/\n/g, '\\n')}
TEL;type=CELL;type=VOICE;waid=${number}:${PhoneNumber('+' + number).getNumber('international')}${biz.description ? `
X-WA-BIZ-NAME:${(conn.contacts[njid]?.vname || conn.getName(njid) || name).replace(/\n/, '\\n')}
X-WA-BIZ-DESCRIPTION:${biz.description.replace(/\n/g, '\\n')}
`.trim() : ''}
END:VCARD
`.trim()
            contacts.push({ vcard, displayName: name })

        }
        return await conn.sendMessage(jid, {
            ...options,
            contacts: {
                ...options,
                displayName: (contacts.length >= 2 ? `${contacts.length} kontak` : contacts[0].displayName) || null,
                contacts,
            }
        }, { quoted, ...options })
    }

    /**
     * Reply to a message
     * @param {String} jid
     * @param {String} text
     * @param {proto.WebMessageInfo} quoted
     * @param {Object} options
     */
    conn.reply = (jid, text = '', quoted, options) => {
        return Buffer.isBuffer(text) ? conn.sendFile(jid, text, 'file', '', quoted, false, options) : conn.sendMessage(jid, { ...options, text }, { quoted, mentions: conn.parseMention(text), ...options })
    }

    /**
             * send Button
             * @param {String} jid 
             * @param {String} contentText 
             * @param {String} footer
             * @param {Buffer} buffer
             * @param {String[]|String[][]} buttons
             * @param {import('@adiwajshing/baileys').proto.WebMessageInfo} quoted
             * @param {Object} options
             */

    conn.sendButton = async (jid, text = '', footer = '', buffer, buttons, quoted, options) => {
        let type
        if (Array.isArray(buffer)) (options = quoted, quoted = buttons, buttons = buffer, buffer = null)
        else if (buffer) try { (type = await conn.getFile(buffer), buffer = type.data) } catch { buffer = null }
        if (!Array.isArray(buttons[0]) && typeof buttons[0] === 'string') buttons = [buttons]
        if (!options) options = {}
        let message = {
            ...options,
            [buffer ? 'caption' : 'text']: text || '',
            footer,
            buttons: buttons.map(btn => ({
                buttonId: !nullish(btn[1]) && btn[1] || !nullish(btn[0]) && btn[0] || '',
                buttonText: {
                    displayText: !nullish(btn[0]) && btn[0] || !nullish(btn[1]) && btn[1] || ''
                }
            })),
            ...(buffer ?
                options.asLocation && /image/.test(type.mime) ? {
                    location: {
                        ...options,
                        jpegThumbnail: buffer
                    }
                } : {
                    [/video/.test(type.mime) ? 'video' : /image/.test(type.mime) ? 'image' : 'document']: buffer
                } : {})
        }

        return await conn.sendMessage(jid, message, {
            quoted,
            upload: conn.waUploadToServer,
            mentions: await conn.parseMention(text),
            ...options
        })
    }

    /*conn.sendButton = async (jid, content, footer, button1, row1, quoted, options = {}) => {
        return await conn.sendMessage(jid, {
            text: content,
            footer: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 }
            ],
            headerType: 1,
            //mentions: this.parseMention(content + footer),
            quoted,
        }, {
            quoted,
            ...options
        })
    }

    conn.send2Button = async (jid, content, footer, button1, row1, button2, row2, quoted, options = {}) => {
        return await conn.sendMessage(jid, {
            text: content,
            footer: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 },
                { buttonId: row2, buttonText: { displayText: button2 }, type: 1 }
            ],
            headerType: 1,
            //mentions: this.parseMention(content + footer),
            quoted,
        }, {
            quoted,
            ...options
        })
    }

    conn.send3Button = async (jid, content, footer, button1, row1, button2, row2, button3, row3, quoted, options = {}) => {
        return await conn.sendMessage(jid, {
            text: content,
            footer: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 },
                { buttonId: row2, buttonText: { displayText: button2 }, type: 1 },
                { buttonId: row3, buttonText: { displayText: button3 }, type: 1 }
            ],
            headerType: 1,
            //mentions: this.parseMention(content + footer),
            quoted
        }, {
            quoted,
            ...options
        })
    }

    /**
     * Send Button with Image
     * @param {String} jid
     * @param {String|Buffer} path
     * @param {String} content
     * @param {String} footer
     * @param {String} button1
     * @param {String} row1
     * @param {String} button2
     * @param {String} row2
     * @param {String} button3
     * @param {String} row3
     * @param {Object} quoted
     * @param {Object} options
     */
    /*conn.sendButtonImg = async (jid, path, content, footer, button1, row1, quoted, options = {}) => {
        let type = await conn.getFile(path)
        let { res, data: file } = type
        if (res && res.status !== 200 || file.length <= 65536) {
            try { throw { json: JSON.parse(file.toString()) } }
            catch (e) { if (e.json) throw e.json }
        }
        return await conn.sendMessage(jid, {
            image: { url: file },
            caption: content,
            footerText: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 }
            ],
            headerType: 4,
            quoted,
            //mentions: this.parseMention(content + footer),
        }, {
            quoted,
            upload: conn.waUploadToServer,
            ...options
        })
    }
    conn.send2ButtonImg = async (jid, path, content, footer, button1, row1, button2, row2, quoted, options = {}) => {
        let type = await conn.getFile(path)
        let { res, data: file } = type
        if (res && res.status !== 200 || file.length <= 65536) {
            try { throw { json: JSON.parse(file.toString()) } }
            catch (e) { if (e.json) throw e.json }
        }
        return await this.sendMessage(jid, {
            image: { url: file },
            contentText: content,
            footerText: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 },
                { buttonId: row2, buttonText: { displayText: button2 }, type: 1 }
            ],
            headerType: 4,
            quoted,
            //mentions: this.parseMention(content + footer),
        }, {
            quoted,
            ...options
        })
    }
    conn.send3ButtonImg = async (jid, path, content, footer, button1, row1, button2, row2, button3, row3, quoted, options = {}) => {
        let type = await conn.getFile(path)
        let { res, data: file } = type
        if (res && res.status !== 200 || file.length <= 65536) {
            try { throw { json: JSON.parse(file.toString()) } }
            catch (e) { if (e.json) throw e.json }
        }
        return await this.sendMessage(jid, {
            image: { url: file },
            caption: content,
            footerText: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 },
                { buttonId: row2, buttonText: { displayText: button2 }, type: 1 },
                { buttonId: row3, buttonText: { displayText: button3 }, type: 1 }
            ],
            headerType: 4,
            quoted,
            //mentions: this.parseMention(content + footer),
        }, {
            quoted,
            ...options
        })
    }

    /**
         * Send Buttons with Location
         * @param {String} jid
         * @param {String|Buffer} path
         * @param {String} content
         * @param {String} footer
         * @param {String} button1
         * @param {String} row1
         * @param {String} button2
         * @param {String} row2
         * @param {String} button3
         * @param {String} row3
         * @param {Object} quoted
         * @param {Object} options
         */
    /*conn.sendButtonLoc = async (jid, path, content, footer, button1, row1, quoted, options = {}) => {
        let type = await conn.getFile(path)
        let { res, data: file } = type
        if (res && res.status !== 200 || file.length <= 65536) {
            try { throw { json: JSON.parse(file.toString()) } }
            catch (e) { if (e.json) throw e.json }
        }
        return await this.sendMessage(jid, {
            location: { jpegThumbnail: file },
            caption: content,
            footer: footer,
            buttons: [{ buttonId: row1, buttonText: { displayText: button1 }, type: 1 }],
            headerType: 6,
            quoted,
            //mentions: this.parseMention(content + footer),
        }, {
            quoted,
            ...options
        })
    }
    conn.send2ButtonLoc = async (jid, path, content, footer, button1, row1, button2, row2, quoted, options = {}) => {
        let type = await this.getFile(path)
        let { res, data: file } = type
        if (res && res.status !== 200 || file.length <= 65536) {
            try { throw { json: JSON.parse(file.toString()) } }
            catch (e) { if (e.json) throw e.json }
        }
        return await this.sendMessage(jid, {
            location: { jpegThumbnail: file },
            caption: content,
            footer: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 },
                { buttonId: row2, buttonText: { displayText: button2 }, type: 1 }
            ],
            headerType: 6,
            quoted,
            //mentions: this.parseMention(content + footer),
        }, {
            quoted,
            ...options
        })
    }
    conn.send3ButtonLoc = async (jid, path, content, footer, button1, row1, button2, row2, button3, row3, quoted, options = {}) => {
        let type = await this.getFile(path)
        let { res, data: file } = type
        if (res && res.status !== 200 || file.length <= 65536) {
            try { throw { json: JSON.parse(file.toString()) } }
            catch (e) { if (e.json) throw e.json }
        }
        return await this.sendMessage(jid, {
            location: { jpegThumbnail: file },
            caption: content,
            footer: footer,
            buttons: [
                { buttonId: row1, buttonText: { displayText: button1 }, type: 1 },
                { buttonId: row2, buttonText: { displayText: button2 }, type: 1 },
                { buttonId: row3, buttonText: { displayText: button3 }, type: 1 }
            ],
            headerType: 6,
            quoted,
            //mentions: this.parseMention(content + footer),
        }, {
            quoted,
            ...options
        })
    }*/

    /**
     * 
     * @param {String} jid 
     * @param {String} text 
     * @param {String} footer 
     * @param {fs.PathLike} buffer
     * @param {String|string[]} url
     * @param {String|string[]} urlText
     * @param {String|string[]} call
     * @param {String|string[]} callText
     * @param {String[][]} buttons
     * @param {import('@adiwajshing/baileys').proto.WebMessageInfo} quoted
     * @param {Object} options
     */
    conn.sendHydrated = async (jid, text = '', footer = '', buffer, url, urlText, call, callText, buttons, quoted, options) => {
        let type
        if (buffer) try { (type = await conn.getFile(buffer), buffer = type.data) } catch { buffer = buffer }
        if (buffer && !Buffer.isBuffer(buffer) && (typeof buffer === 'string' || Array.isArray(buffer))) (options = quoted, quoted = buttons, buttons = callText, callText = call, call = urlText, urlText = url, url = buffer, buffer = null)
        if (!options) options = {}
        let templateButtons = []
        if (url || urlText) {
            if (!Array.isArray(url)) url = [url]
            if (!Array.isArray(urlText)) urlText = [urlText]
            templateButtons.push(...(
                url.map((v, i) => [v, urlText[i]])
                    .map(([url, urlText], i) => ({
                        index: templateButtons.length + i + 1,
                        urlButton: {
                            displayText: !nullish(urlText) && urlText || !nullish(url) && url || '',
                            url: !nullish(url) && url || !nullish(urlText) && urlText || ''
                        }
                    })) || []
            ))
        }
        if (call || callText) {
            if (!Array.isArray(call)) call = [call]
            if (!Array.isArray(callText)) callText = [callText]
            templateButtons.push(...(
                call.map((v, i) => [v, callText[i]])
                    .map(([call, callText], i) => ({
                        index: templateButtons.length + i + 1,
                        callButton: {
                            displayText: !nullish(callText) && callText || !nullish(call) && call || '',
                            phoneNumber: !nullish(call) && call || !nullish(callText) && callText || ''
                        }
                    })) || []
            ))
        }
        if (buttons.length) {
            if (!Array.isArray(buttons[0])) buttons = [buttons]
            templateButtons.push(...(
                buttons.map(([text, id], index) => ({
                    index: templateButtons.length + index + 1,
                    quickReplyButton: {
                        displayText: !nullish(text) && text || !nullish(id) && id || '',
                        id: !nullish(id) && id || !nullish(text) && text || ''
                    }
                })) || []
            ))
        }
        let message = {
            ...options,
            [buffer ? 'caption' : 'text']: text || '',
            footer,
            templateButtons,
            ...(buffer ?
                options.asLocation && /image/.test(type.mime) ? {
                    location: {
                        ...options,
                        jpegThumbnail: buffer
                    }
                } : {
                    [/video/.test(type.mime) ? 'video' : /image/.test(type.mime) ? 'image' : 'document']: buffer
                } : {})
        }
        return await conn.sendMessage(jid, message, {
            quoted,
            upload: conn.waUploadToServer,
            ...options
        })
    }

    /**
             * cMod
             * @param {String} jid 
             * @param {proto.WebMessageInfo} message 
             * @param {String} text 
             * @param {String} sender 
             * @param {*} options 
             * @returns 
             */
    conn.cMod = async (jid, message, text = '', sender = conn.user.jid, options = {}) => {
        if (options.mentions && !Array.isArray(options.mentions)) options.mentions = [options.mentions]
        let copy = message.toJSON()
        delete copy.message.messageContextInfo
        delete copy.message.senderKeyDistributionMessage
        let mtype = Object.keys(copy.message)[0]
        let msg = copy.message
        let content = msg[mtype]
        if (typeof content === 'string') msg[mtype] = text || content
        else if (content.caption) content.caption = text || content.caption
        else if (content.text) content.text = text || content.text
        if (typeof content !== 'string') {
            msg[mtype] = { ...content, ...options }
            msg[mtype].contextInfo = {
                ...(content.contextInfo || {}),
                mentionedJid: options.mentions || content.contextInfo?.mentionedJid || []
            }
        }
        if (copy.participant) sender = copy.participant = sender || copy.participant
        else if (copy.key.participant) sender = copy.key.participant = sender || copy.key.participant
        if (copy.key.remoteJid.includes('@s.whatsapp.net')) sender = sender || copy.key.remoteJid
        else if (copy.key.remoteJid.includes('@broadcast')) sender = sender || copy.key.remoteJid
        copy.key.remoteJid = jid
        copy.key.fromMe = areJidsSameUser(sender, conn.user.id) || false
        return proto.WebMessageInfo.fromObject(copy)
    }


    /**
     * Exact Copy Forward
     * @param {String} jid
     * @param {proto.WebMessageInfo} message
     * @param {Boolean|Number} forwardingScore
     * @param {Object} options
     */
    conn.copyNForward = async (jid, message, forwardingScore = true, options = {}) => {
        let vtype
        if (options.readViewOnce) {
            message.message = message?.message?.ephemeralMessage?.message ? message.message.ephemeralMessage.message : (message.message || undefined)
            vtype = Object.keys(message.message.viewOnceMessage.message)[0]
            delete (message.message?.ignore ? message.message.ignore : (message.message || undefined))
            delete message.message.viewOnceMessage.message[vtype].viewOnce
            message.message = {
                ...message.message.viewOnceMessage.message
            }
        }
        let m = generateForwardMessageContent(message, !!forwardingScore)
        let mtype = Object.keys(m)[0]
        if (forwardingScore && typeof forwardingScore == 'number' && forwardingScore > 1) m[mtype].contextInfo.forwardingScore += forwardingScore
        m = generateWAMessageFromContent(jid, m, { ...options, userJid: conn.user.id })
        await conn.relayMessage(jid, m.message, { messageId: m.key.id, additionalAttributes: { ...options } })
        return m
    }

    /**
     * Download media message
     * @param {Object} m
     * @param {String} type 
     * @param {fs.PathLike|fs.promises.FileHandle} filename
     * @returns {Promise<fs.PathLike|fs.promises.FileHandle|Buffer>}
     */
    conn.downloadM = async (m, type, saveToFile) => {
        if (!m || !(m.url || m.directPath)) return Buffer.alloc(0)
        const stream = await downloadContentFromMessage(m, type)
        let buffer = Buffer.from([])
        for await (const chunk of stream) {
            buffer = Buffer.concat([buffer, chunk])
        }
        if (saveToFile) var { filename } = await conn.getFile(buffer, true)
        return saveToFile && fs.existsSync(filename) ? filename : buffer
    }

    /**
     * Read message
     * @param {String} jid 
     * @param {String|undefined|null} participant 
     * @param {String} messageID 
     */
    conn.chatRead = conn.chatRead || (async (jid, participant = conn.user.jid, messageID) => {
        return await conn.sendReadReceipt(jid, participant, [messageID])
    })

    /**
     * Parses string into mentionedJid(s)
     * @param {String} text
     * @returns {Array<String>}
     */
    conn.parseMention = (text = '') => {
        return [...text.matchAll(/@([0-9]{5,16}|0)/g)].map(v => v[1] + '@s.whatsapp.net')
    }

    /**
     * Get name from jid
     * @param {String} jid
     * @param {Boolean} withoutContact
     */
    conn.getName = (jid = '', withoutContact = false) => {
        jid = conn.decodeJid(jid)
        withoutContact = conn.withoutContact || withoutContact
        let v
        if (jid.endsWith('@g.us')) return new Promise(async (resolve) => {
            v = conn.chats[jid] || {}
            if (!(v.name || v.subject)) v = await conn.groupMetadata(jid) || {}
            resolve(v.name || v.subject || PhoneNumber('+' + jid.replace('@s.whatsapp.net', '')).getNumber('international'))
        })
        else v = jid === '0@s.whatsapp.net' ? {
            jid,
            vname: 'WhatsApp'
        } : areJidsSameUser(jid, conn.user.id) ?
            conn.user :
            (conn.chats[jid] || {})
        return (withoutContact ? '' : v.name) || v.subject || v.vname || v.notify || v.verifiedName || PhoneNumber('+' + jid.replace('@s.whatsapp.net', '')).getNumber('international')
    }

    conn.loadMessage = conn.loadMessage || (async (messageID) => {
        return Object.entries(conn.chats)
            .filter(([_, { messages }]) => typeof messages === 'object')
            .find(([_, { messages }]) => Object.entries(messages)
                .find(([k, v]) => (k === messageID || v.key?.id === messageID)))
            ?.[1].messages?.[messageID]
    })

    /**
     * to process MessageStubType
     * @param {import('@adiwajshing/baileys').proto.WebMessageInfo} m 
     */
    conn.processMessageStubType = async (m) => {
        if (!m.messageStubType) return
        const chat = conn.decodeJid(m.key.remoteJid || m.message?.senderKeyDistributionMessage?.groupId || '')
        if (!chat || chat === 'status@broadcast') return
        const isGroup = chat.endsWith('@g.us')
        if (!isGroup) return
        let chats = conn.chats[chat]
        if (!chats) chats = conn.chats[chat] = { id: chat }
        chats.isChats = true
        const metadata = await conn.groupMetadata(chat).catch(_ => null)
        if (!metadata) return
        chats.subject = metadata.subject
        chats.metadata = metadata
    }

    /**
     * pushMessage
     * @param {import('@adiwajshing/baileys').proto.WebMessageInfo[]} m 
     */
    conn.pushMessage = async (m) => {
        if (!m) return
        if (!Array.isArray(m)) m = [m]
        for (const message of m) {
            try {
                // if (!(message instanceof proto.WebMessageInfo)) continue // https://github.com/adiwajshing/Baileys/pull/696/commits/6a2cb5a4139d8eb0a75c4c4ea7ed52adc0aec20f
                if (!message) continue
                if (message.messageStubType && message.messageStubType != WAMessageStubType.CIPHERTEXT) conn.processMessageStubType(message).catch(console.error)
                const _mtype = Object.keys(message.message || {})
                const mtype = (!['senderKeyDistributionMessage', 'messageContextInfo'].includes(_mtype[0]) && _mtype[0]) ||
                    (_mtype.length >= 3 && _mtype[1] !== 'messageContextInfo' && _mtype[1]) ||
                    _mtype[_mtype.length - 1]
                const chat = conn.decodeJid(message.key.remoteJid || message.message?.senderKeyDistributionMessage?.groupId || '')
                if (message[mtype]?.contextInfo?.quotedMessage) console.log(message[mtype].contextInfo)
                if (!chat || chat === 'status@broadcast') continue
                const isGroup = chat.endsWith('@g.us')
                let chats = conn.chats[chat]
                if (!chats) {
                    if (isGroup) {
                        const groups = await conn.groupFetchAllParticipating().catch(_ => ({}))
                        for (const group in groups) conn.chats[group] = { id: group, subject: groups[group].subject, isChats: true, metadata: groups[group] }
                    }
                    chats = conn.chats[chat] = { id: chat, ...(conn.chats[chat] || {}) }
                }
                let metadata, sender
                if (isGroup) {
                    if (!chats.subject || !chats.metadata) {
                        metadata = await conn.groupMetadata(chat).catch(_ => ({})) || {}
                        if (!chats.subject) chats.subject = metadata.subject || ''
                        if (!chats.metadata) chats.metadata = metadata
                    }
                    sender = conn.decodeJid(message.fromMe && conn.user.id || message.participant || message.key.participant || chat || '')
                    if (sender !== chat) {
                        let chats = conn.chats[sender]
                        if (!chats) chats = conn.chats[sender] = { id: sender }
                        if (!chats.name) chats.name = message.pushName || chats.name || ''
                    }
                } else if (!chats.name) chats.name = message.pushName || chats.name || ''
                if (['senderKeyDistributionMessage', 'protocolMessage', 'messageContextInfo'].includes(mtype)) continue
                chats.isChats = true
                const fromMe = message.key.fromMe || areJidsSameUser(chat, conn.user.id)
                if (!chats.messages) chats.messages = {}
                if (!fromMe) chats.messages[message.key.id] = JSON.parse(JSON.stringify(message, null, 2))
                // TODO: push quotedMessage
            } catch (e) {
                console.error(e)
            }
        }
    }

    conn.setStatus = conn.setStatus || (async (status) => {
        return await conn.query({
            tag: 'iq',
            attrs: {
                to: conn.user.jid,
                type: 'set',
                xmlns: 'status',
            },
            content: [
                {
                    tag: 'status',
                    attrs: {},
                    content: Buffer.from(status, 'utf-8')
                }
            ]
        })
    })

    conn.setpp = async (jid, buffer) => {
        let { img } = await pplong(buffer)
        await conn.query({
            tag: 'iq',
            attrs: {
                to: jid,
                type: 'set',
                xmlns: 'w:profile:picture'
            },
            content: [{
                tag: 'picture',
                attrs: {
                    type: 'image'
                },
                content: img
            }]
        })

    }


    /**
     * Serialize Message, so it easier to manipulate
     * @param {proto.WebMessageInfo} m
     */
    conn.serializeM = (m) => {
        return smsg(conn, m)
    }

    return conn
}
/**
 * Serialize Message
 * @param {ReturnType<typeof makeWASocket>} conn 
 * @param {import('@adiwajshing/baileys').proto.WebMessageInfo} m 
 * @param {Boolean} hasParent 
 */
export function smsg(conn, m, hasParent) {
    if (!m) return m
    /**
     * @type {import('@adiwajshing/baileys').proto.WebMessageInfo}
     */
    let M = proto.WebMessageInfo
    m = M.fromObject(m)
    if (m.key) {
        m.id = m.key.id
        m.isBaileys = m.id && m.id.length === 16 || m.id.startsWith('3EB0') && m.id.length === 12 || false
        m.chat = conn.decodeJid(m.key.remoteJid || message.message?.senderKeyDistributionMessage?.groupId || '')
        m.isGroup = m.chat.endsWith('@g.us')
        m.sender = conn.decodeJid(m.key.fromMe && conn.user.id || m.participant || m.key.participant || m.chat || '')
        m.fromMe = m.key.fromMe || areJidsSameUser(m.sender, conn.user.id)
    }
    if (m.message) {
        let mtype = Object.keys(m.message)
        m.mtype = (!['senderKeyDistributionMessage', 'messageContextInfo'].includes(mtype[0]) && mtype[0]) || // Sometimes message in the front
            (mtype.length >= 3 && mtype[1] !== 'messageContextInfo' && mtype[1]) || // Sometimes message in midle if mtype length is greater than or equal to 3!
            mtype[mtype.length - 1] // common case
        m.msg = m.message[m.mtype]
        if (m.chat == 'status@broadcast' && ['protocolMessage', 'senderKeyDistributionMessage'].includes(m.mtype)) m.chat = (m.key.remoteJid !== 'status@broadcast' && m.key.remoteJid) || m.sender
        if (m.mtype == 'protocolMessage' && m.msg.key) {
            if (m.msg.key.remoteJid == 'status@broadcast') m.msg.key.remoteJid = m.chat
            if (!m.msg.key.participant || m.msg.key.participant == 'status_me') m.msg.key.participant = m.sender
            m.msg.key.fromMe = conn.decodeJid(m.msg.key.participant) === conn.decodeJid(conn.user.id)
            if (!m.msg.key.fromMe && m.msg.key.remoteJid === conn.decodeJid(conn.user.id)) m.msg.key.remoteJid = m.sender
        }
        m.text = m.msg.text || m.msg.caption || m.msg.contentText || m.msg || ''
        if (typeof m.text !== 'string') {
            if ([
                'protocolMessage',
                'messageContextInfo',
                'stickerMessage',
                'audioMessage',
                'senderKeyDistributionMessage'
            ].includes(m.mtype)) m.text = ''
            else m.text = m.text.selectedDisplayText || m.text.hydratedTemplate?.hydratedContentText || m.text
        }
        m.mentionedJid = m.msg?.contextInfo?.mentionedJid?.length && m.msg.contextInfo.mentionedJid || []
        let quoted = m.quoted = m.msg?.contextInfo?.quotedMessage ? m.msg.contextInfo.quotedMessage : null
        if (m.quoted) {
            let type = Object.keys(m.quoted)[0]
            m.quoted = m.quoted[type]
            if (typeof m.quoted === 'string') m.quoted = { text: m.quoted }
            m.quoted.mtype = type
            m.quoted.id = m.msg.contextInfo.stanzaId
            m.quoted.chat = conn.decodeJid(m.msg.contextInfo.remoteJid || m.chat || m.sender)
            m.quoted.isBaileys = m.quoted.id && m.quoted.id.length === 16 || false
            m.quoted.sender = conn.decodeJid(m.msg.contextInfo.participant)
            m.quoted.fromMe = m.quoted.sender === conn.user.jid
            m.quoted.text = m.quoted.text || m.quoted.caption || m.quoted.contentText || ''
            m.quoted.name = conn.getName(m.quoted.sender)
            m.quoted.mentionedJid = m.quoted.contextInfo?.mentionedJid?.length && m.quoted.contextInfo.mentionedJid || []
            let vM = m.quoted.fakeObj = M.fromObject({
                key: {
                    fromMe: m.quoted.fromMe,
                    remoteJid: m.quoted.chat,
                    id: m.quoted.id
                },
                message: quoted,
                ...(m.isGroup ? { participant: m.quoted.sender } : {})
            })
            m.getQuotedObj = m.getQuotedMessage = async () => {
                if (!m.quoted.id) return null
                let q = M.fromObject(await conn.loadMessage(m.quoted.id) || vM)
                return smsg(conn, q)
            }

            if (m.quoted.url || m.quoted.directPath) m.quoted.download = (saveToFile = false) => conn.downloadM(m.quoted, m.quoted.mtype.replace(/message/i, ''), saveToFile)

            /**
             * Reply to quoted message
             * @param {String|Object} text
             * @param {String|false} chatId
             * @param {Object} options
             */
            m.quoted.reply = (text, chatId, options) => conn.reply(chatId ? chatId : m.chat, text, vM, options)

            /**
             * Copy quoted message
             */
            m.quoted.copy = () => smsg(conn, M.fromObject(M.toObject(vM)))

            /**
             * Forward quoted message
             * @param {String} jid
             *  @param {Boolean} forceForward
            */
            m.quoted.forward = (jid, forceForward = false) => conn.forwardMessage(jid, vM, forceForward)

            /**
             * Exact Forward quoted message
             * @param {String} jid
             * @param {Boolean|Number} forceForward
             * @param {Object} options
            */
            m.quoted.copyNForward = (jid, forceForward = true, options = {}) => conn.copyNForward(jid, vM, forceForward, options)

            /**
             * Modify quoted Message
             * @param {String} jid
             * @param {String} text
             * @param {String} sender
             * @param {Object} options
            */
            m.quoted.cMod = (jid, text = '', sender = m.quoted.sender, options = {}) => conn.cMod(jid, vM, text, sender, options)

            /**
             * Delete quoted message
             */
            m.quoted.delete = () => conn.sendMessage(m.quoted.chat, { delete: vM.key })
        }
    }
    m.name = !nullish(m.pushName) && m.pushName || conn.getName(m.sender)
    if (m.msg && m.msg.url) m.download = (saveToFile = false) => conn.downloadM(m.msg, m.mtype.replace(/message/i, ''), saveToFile)
    /**
     * Reply to this message
     * @param {String|Object} text
     * @param {String|false} chatId
     * @param {Object} options
     */
    m.reply = (text, chatId, options) => {
        return conn.reply(chatId ? chatId : m.chat, text, m, options)
    }

    /**
     * Copy this message
     */
    m.copy = () => smsg(conn, M.fromObject(M.toObject(m)))

    /**
     * Forward this message
     * @param {String} jid
     * @param {Boolean} forceForward
     */
    m.forward = (jid = m.chat, forceForward = false) => conn.copyNForward(jid, m, forceForward)

    /**
     * Exact Forward this message
     * @param {String} jid
     * @param {Boolean} forceForward
     * @param {Object} options
     */
    m.copyNForward = (jid = m.chat, forceForward = true, options = {}) => conn.copyNForward(jid, m, forceForward, options)

    /**
     * Modify this Message
     * @param {String} jid 
     * @param {String} text 
     * @param {String} sender 
     * @param {Object} options 
     */
    m.cMod = (jid, text = '', sender = m.sender, options = {}) => conn.cMod(jid, m, text, sender, options)

    /**
     * Delete this message
     */
    m.delete = () => conn.sendMessage(m.chat, { delete: m.key })

    try {
        if (m.msg && m.mtype == 'protocolMessage' && m.msg.key) conn.ev.emit('message.delete', m.msg.key)
    } catch (e) {
        console.error(e)
    }
    return m
}

export function logic(check, inp, out) {
    if (inp.length !== out.length) throw new Error('Input and Output must have same length')
    for (let i in inp) if (util.isDeepStrictEqual(check, inp[i])) return out[i]
    return null
}

export function protoType() {
    Buffer.prototype.toArrayBuffer = function toArrayBufferV2() {
        const ab = new ArrayBuffer(this.length);
        const view = new Uint8Array(ab);
        for (let i = 0; i < this.length; ++i) {
            view[i] = this[i];
        }
        return ab;
    }
    /**
     * @returns {ArrayBuffer}
     */
    Buffer.prototype.toArrayBufferV2 = function toArrayBuffer() {
        return this.buffer.slice(this.byteOffset, this.byteOffset + this.byteLength)
    }
    /**
     * @returns {Buffer}
     */
    ArrayBuffer.prototype.toBuffer = function toBuffer() {
        return Buffer.from(new Uint8Array(this))
    }
    // /**
    //  * @returns {String}
    //  */
    // Buffer.prototype.toUtilFormat = ArrayBuffer.prototype.toUtilFormat = Object.prototype.toUtilFormat = Array.prototype.toUtilFormat = function toUtilFormat() {
    //     return util.format(this)
    // }
    Uint8Array.prototype.getFileType = ArrayBuffer.prototype.getFileType = Buffer.prototype.getFileType = async function getFileType() {
        return await fileTypeFromBuffer(this)
    }
    /**
     * @returns {Boolean}
     */
    String.prototype.isNumber = Number.prototype.isNumber = isNumber
    /**
     * 
     * @returns {String}
     */
    String.prototype.capitalize = function capitalize() {
        return this.charAt(0).toUpperCase() + this.slice(1, this.length)
    }
    /**
     * @returns {String}
     */
    String.prototype.capitalizeV2 = function capitalizeV2() {
        const str = this.split(' ')
        return str.map(v => v.capitalize()).join(' ')
    }
    /**
     * number must be milliseconds
     * @returns {string}
     */
    Number.prototype.toTimeString = function toTimeString() {
        // const milliseconds = this % 1000
        const seconds = Math.floor((this / 1000) % 60)
        const minutes = Math.floor((this / (60 * 1000)) % 60)
        const hours = Math.floor((this / (60 * 60 * 1000)) % 24)
        const days = Math.floor((this / (24 * 60 * 60 * 1000)))
        return (
            (days ? `${days} day(s) ` : '') +
            (hours ? `${hours} hour(s) ` : '') +
            (minutes ? `${minutes} minute(s) ` : '') +
            (seconds ? `${seconds} second(s)` : '')
        ).trim()
    }
    Number.prototype.getRandom = String.prototype.getRandom = Array.prototype.getRandom = getRandom
}


function isNumber() {
    const int = parseInt(this)
    return typeof int === 'number' && !isNaN(int)
}

function getRandom() {
    if (this instanceof Array || this instanceof String) return this[Math.floor(Math.random() * this.length)]
    if (this instanceof Number) return Math.floor(Math.random() * this)
}


/**
 * ??
 * @link https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Operators/Nullish_coalescing_operator
 * @returns {boolean}
 */
function nullish(args) {
    return !(args !== null && args !== undefined)
}

async function pplong(buffer) {
    const jimp = await Jimp.read(buffer)
    const min = jimp.getWidth()
    const max = jimp.getHeight()
    const cropped = jimp.crop(0, 0, min, max)
    return {
        img: await cropped.scaleToFit(720, 720).getBufferAsync(Jimp.MIME_JPEG),
        preview: await cropped.scaleToFit(720, 720).getBufferAsync(Jimp.MIME_JPEG)
    }
}
