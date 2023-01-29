let handler = async (m, { conn, usedPrefix: _p }) => {
    let help = Object.values(global.plugins).filter(plugin => !plugin.disabled).map(plugin => {
        return {
            help: Array.isArray(plugin.tags) ? plugin.help : [plugin.help],
            tags: Array.isArray(plugin.tags) ? plugin.tags : [plugin.tags],
            prefix: 'customPrefix' in plugin,
            limit: plugin.limit,
            premium: plugin.premium,
            enabled: !plugin.disabled,
        }
    })
    const plugin = help.filter(plugin => plugin.tags[0] === 'panel')[0].help
    let t = `*===[ PANEL MENU ]===*
\n`
    // for (let p of plugin)
    //     t += `${p++}. ${p}\n`
    // console.log(JSON.stringify(t))
    await plugin.map((v, index) => t += `- ${_p + v}\n`)
    m.reply(t)
}

handler.command = ['ptero', 'panel', 'pmenu', 'phelp']

export default handler
